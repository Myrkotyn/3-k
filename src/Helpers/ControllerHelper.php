<?php

namespace App\Helpers;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

trait ControllerHelper
{
    /**
     * @param $object
     * @param $format
     * @param $attributes
     * @return string|\Symfony\Component\Serializer\Encoder\scalar
     */
    private function serialize($object, $format = null, $attributes = null)
    {
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        return $serializer->serialize($object, $format, ["groups" => "group1"]);
    }
}