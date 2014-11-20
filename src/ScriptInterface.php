<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 15/11/14
 * Time: 16:03
 */

namespace Bitcoin;


interface ScriptInterface
{

    /**
     * Return the hex string of the script
     * @return mixed
     */
    public function getHex();

    /**
     * //TODO: this needed?
     * @return mixed
     */
    public function serialize();

} 