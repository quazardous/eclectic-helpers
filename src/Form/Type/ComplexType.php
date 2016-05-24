<?php
namespace Quazardous\Eclectic\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * An input type to hold pseudo class form.
 * 
 * It's usefull for tabular forms. Say you need a delete button for each item in a list:
 * <code>
 *   $form = $app->namedForm('my_list_form', $data)
 *     ->add('jobs', CollectionType::class, [
 *       'entry_type' => ComplexType::class,
 *       'entry_options' => [
 *         'fields' => [
 *           'delete' => [
 *             'type' => SubmitType::class,
 *           ],
 *         ],
 *       ],
 *       'allow_add'    => false,
 *       'allow_delete' => false,
 *       'required'     => false,
 *       'delete_empty' => false,
 *       'label' => false,
 *     ])->getForm();
 * </code>
 *
 */
class ComplexType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($options['fields'] as $name => $field) {
            $field += [
                'options' => [],
            ];
            $builder->add($name, $field['type'], $field['options']);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'fields' => [],
        ]);
    }
}