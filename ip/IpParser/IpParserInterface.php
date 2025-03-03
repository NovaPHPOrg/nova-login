<?php
/*
 * Copyright (c) 2023. Ankio. All Rights Reserved.
 */

namespace nova\plugin\login\ip\IpParser;

interface IpParserInterface
{
    function setDBPath($filePath);

    /**
     * @param $ip
     * @return mixed ['ip', 'country', 'area']
     */
    function getIp($ip);
}