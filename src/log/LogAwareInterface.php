<?php
# LogAwareInterface.php

namespace j\log;

/**
 * User: Administrator
 * Date: 2017/9/25 0025
 * Time: 下午 15:51
 */

interface LogAwareInterface {
    /**
     * @param Logger $logger
     */
    public function setLogger($logger);

}