<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 15/11/14
 * Time: 16:03
 */

namespace Bitcoin;


interface ScriptInterface {
    public function getChunks();
    public function serialize();

} 