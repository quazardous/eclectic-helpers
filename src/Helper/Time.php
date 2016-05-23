<?php

namespace Quazardous\Eclectic\Helper;

class Time
{
    /**
     * Add some fuzzyness to the given interval
     * @param \DateInterval $interval
     * @param real $fuzziness
     * @param real $mode
     *  - mode < 0 : for 0.1 in fuzziness it means -10% +0%
     *  - mode == 0 : for 0.1 in fuzziness it means -5% +5%
     *  - mode > 0 : for 0.1 in fuzziness it means -0% +10%
     * @return string
     */
    public static function fuzzyIntervalString(\DateInterval $interval, $fuzziness = 0.1, $mode = 0)
    {
        $reference = new \DateTimeImmutable;
        $endTime = $reference->add($interval);
        
        $seconds = $endTime->getTimestamp() - $reference->getTimestamp();
        
        $range = $seconds * $fuzziness;
        $rand = rand(0, $range);
        if ($mode < 0) {
            $seconds -= $rand;    
        } elseif ($mode > 0) {
            $seconds += $rand;
        } else {
            $seconds += $rand - $range/2;
        }
        
        return sprintf("PT%dS", $seconds);
    }
    
    /**
     * Add some fuzzyness to the given interval
     * @param \DateInterval $interval
     * @param real $fuzziness
     * @param number $mode
     * @return \DateInterval
     */
    public static function fuzzyInterval(\DateInterval $interval, $fuzziness = 0.1, $mode = 0)
    {
        return new \DateInterval(static::fuzzyIntervalString($interval, $fuzziness, $mode));
    }
    
    /**
     * Return the given ratio of the given interval.
     * @param \DateInterval $interval
     * @param number $ratio 0 to 1
     * @return string
     */
    public static function ratioIntervalString(\DateInterval $interval, $ratio)
    {
        $reference = new \DateTimeImmutable;
        $endTime = $reference->add($interval);
    
        $seconds = $endTime->getTimestamp() - $reference->getTimestamp();
    
        $seconds = $seconds * $ratio;
    
        return sprintf("PT%dS", $seconds);
    }
    
    /**
     * Return the given ratio of the given interval.
     * @param \DateInterval $interval
     * @param number $ratio 0 to 1
     * @return \DateInterval
     */
    public static function ratioInterval(\DateInterval $interval, $ratio)
    {
        return new \DateInterval(static::ratioIntervalString($interval, $ratio));
    }
    
    /**
     * Ensure the date can be a correct \DateTime.
     * @param \DateTime|string $date
     * @param string $format
     * @throws \InvalidArgumentException
     * @return \DateTime
     */
    public static function ensureDateTime($date, $format = "Y-m-d H:i:s")
    {
        if (empty($date)) {
            new \InvalidArgumentException('Incorrect date');
        }
        if (is_string($date)) {
            $date = \DateTime::createFromFormat($format, $date);
        }
        if ($date instanceof \DateTime) {
            return $date;
        }
        throw new \InvalidArgumentException('Incorrect date');
    }
    
    /**
     * Ensure the date can be a correct date string.
     * @param \DateTime|string $date
     * @param string $format
     * @throws \InvalidArgumentException
     */
    public static function ensureString($date, $format = "Y-m-d H:i:s")
    {
        if (empty($date)) {
            new \InvalidArgumentException('Incorrect date');
        }
        if (is_string($date)) {
            $date = \DateTime::createFromFormat($format, $date);
        }
        if ($date instanceof \DateTime) {
            return $date->format($format);
        }
        throw new \InvalidArgumentException('Incorrect date');
    }
}