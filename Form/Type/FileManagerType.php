<?php

namespace Youwe\FileManagerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @author Jim Ouwerkerk <j.ouwerkerk@youwe.nl>
 *
 * Class FileManager
 * @package Youwe\FileManagerBundle\Form\Type
 */
class FileManagerType extends AbstractType {

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('file', 'file', array(
            'required'    => FALSE,
            'attr'        => array('class' => 'form-control file_manager_url', 'multiple' => 'multiple'),
            'label'       => 'Files'
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
        return 'file_manager';
    }
}
