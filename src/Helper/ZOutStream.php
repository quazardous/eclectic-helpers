<?php

namespace Quazardous\Eclectic\Helper;

/**
 * This class wil provide on-the-fly zlib compression to the web output.
 * <code>
 * $zout = new ZOutStream('my_big_file.txt');
 * $zout->open();
 * $zout->put(file_get_contents('/path/to/my_big_file.txt'));
 * $zout->close();
 * </code>
 */
class ZOutStream
{
    protected $filename;   
    protected $mtime;
    protected $options = [
        'buffer_size' => 64*1024,
    ];
    
    /**
     * Create the Z stream
     * @param string $filename name to give to the zipped file
     * @param number $mtime a modification stamp
     * @param array $options
     */
    public function __construct($filename, $mtime = null, array $options = [])
    {
        $this->filename = $filename;
        $this->mtime = $mtime ? $mtime : time();
        $this->options = array_replace($this->options, $options);
    }
    
    protected $fd = null;
    protected $fsize;
    protected $fltr;
    protected $hctx;
    
    /**
     * Init the Z stream.
     * @return boolean|NULL|resource
     */
    public function open()
    {
        $this->fd = fopen("php://output", "wb");
        if (false === $this->fd)  return false;
        
        // write gzip header
        fwrite($this->fd, "\x1F\x8B\x08\x08" . pack("V", $this->mtime) . "\0\xFF", 10);
        // write the original file name
        $oname = str_replace("\0", "", basename($this->filename));
        fwrite($this->fd, $oname . "\0", 1 + strlen($oname));
        // add the deflate filter using default compression level
        $this->fltr = stream_filter_append($this->fd, "zlib.deflate", STREAM_FILTER_WRITE, -1);
        // set up the CRC32 hashing context
        $this->hctx = hash_init("crc32b");
        
        $this->fsize = 0;
        $this->buffer = null;
        return $this->fd;
    }
    
    protected $buffer;
    
    /**
     * Buffered stream content.
     * This function handle a buffer for better compression.
     * @param string $content
     * @throws \RuntimeException
     */
    public function put($content, $flush = false)
    {
        if (!$this->fd) throw new \RuntimeException('Stream is not initialized');
        $this->buffer .= $content;        
        return $this->flush($flush);
    }
    
    /**
     * Handle a (compression) buffer.
     * @param string $force
     * @return number|boolean
     */
    protected function flush($force = false) {
        $clen = strlen($this->buffer);
        if ($force || $clen >= $this->options['buffer_size']) {
            $res = $this->_write($this->buffer);
            $this->buffer = null;
            return $res;
        }
        return true;
    }
    
    /**
     * Direct stream.
     * @param string $content
     */
    public function write($content) {
        $this->flush(true);
        return $this->_write($content);
    }
    
    protected function _write($content) {
        hash_update($this->hctx, $content);
        $clen = strlen($content);
        $this->fsize += $clen;
        return fwrite($this->fd, $content, $clen);
    }
    
    /**
     * Close the Z stream.
     * @return boolean
     */
    public function close()
    {
        $this->flush(true);
        
        // remove the deflate filter
        stream_filter_remove($this->fltr);
        // write the CRC32 value
        // hash_final is a string, not an integer
        $crc = hash_final($this->hctx, TRUE);
        // need to reverse the hash_final string so it's little endian
        fwrite($this->fd, $crc[3] . $crc[2] . $crc[1] . $crc[0], 4);
        // write the original uncompressed file size
        fwrite($this->fd, pack("V", $this->fsize), 4);
        $ret = fclose($this->fd);
        
        $this->fd = null;
        
        return $ret;
    }
}
