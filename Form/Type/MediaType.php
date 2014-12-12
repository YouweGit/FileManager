<?php

namespace Youwe\MediaBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @author Jim Ouwerkerk (j.ouwerkerk@youwe.nl)
 *
 * Class MediaType
 *
 * @package Youwe\MediaBundle\Form\Type
 */
class MediaType extends AbstractType {

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('file', 'file', array(
            'required'    => FALSE,
            'attr'        => array('class' => 'form-control media_url', 'multiple' => 'multiple'),
            'label'       => 'Media'
        ));

        $builder->add('newfolder');
        $builder->add('rename_file');
        $builder->add('origin_file_name');
        $builder->add('origin_file_ext');
    }

    /**
     * @return string
     */
    public function getName() {
        return 'media';
    }
}
