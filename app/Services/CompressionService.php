<?php

namespace App\Services;

class CompressionService
{
    protected $compressAttributes = [
        'User' => [
             'email'
        ],

        'Tenants' => [
            'email','phone' ,
            'id_number', 'blood_group' ,
        ],

        'UserDetails' => [
            'username', 'phone', 'gender',
            'country', 'id_number', 'address',
            'location', 'about_the_user',
        ]
    ];

    public function compressAttribute($value)
    {
        return gzcompress($value);
    }

    public function decompressAttribute($value)
    {
        return gzuncompress($value);
    }

//    public function compressModelAttributes($model)
//    {
//        $modelName = class_basename($model);
//        $compressedAttributes = [];
//        if (isset($this->compressAttributes[$modelName])) {
//            foreach ($this->compressAttributes[$modelName] as $attribute) {
//                if (isset($model->$attribute)) {
//                    $compressedAttributes[$attribute] = $this->compressAttribute($model->$attribute);
//                }
//            }
//        }
//        // Update the model with compressed attributes
//        $model->setRawAttributes($compressedAttributes, true);
//        return $model;
//    }


    public function decompressModelAttributes($model)
    {
        $modelName = class_basename($model);
        $decompressedAttributes = $model->getAttributes();

        if (isset($this->compressAttributes[$modelName])) {
            foreach ($this->compressAttributes[$modelName] as $attribute) {
                if (isset($decompressedAttributes[$attribute])) {
                    $decompressedAttributes[$attribute] = $this->decompressAttribute($decompressedAttributes[$attribute]);
                }
            }
        }

        // Update the model with decompressed attributes
        $model->setRawAttributes($decompressedAttributes, true);
        return $model;
    }

}
