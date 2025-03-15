<?php

declare(strict_types=1);
/*******************************************************************************
 * Copyright (c) 2022. Ankio. All Rights Reserved.
 ******************************************************************************/
/**
 * Package: nova\plugin\login\device
 * Class UserAgent
 * Created By ankio.
 * Date : 2023/7/13
 * Time : 21:08
 * Description : 用户代理解析类，用于识别用户的操作系统和浏览器信息
 */

namespace nova\plugin\login\device;

/**
 * UserAgent类
 * 负责解析HTTP请求中的User-Agent字符串，提取操作系统和浏览器信息
 */
class UserAgent
{
    /**
     * 解析用户代理字符串
     *
     * @param  string $ua 用户代理字符串
     * @return array  返回包含操作系统名称、操作系统图标、浏览器名称和浏览器图标的数组
     */
    public static function parse($ua): array
    {
        // 获取操作系统信息
        $Os = Os::get($ua);
        $OsImg = self::img("os/", $Os['code'], $Os['title']);
        $OsName = $Os['title'];

        // 获取浏览器信息
        $Browser = Browser::get($ua);
        $BrowserImg = self::img("browser/", $Browser['code'], $Browser['title']);
        $BrowserName = $Browser['title'];

        // 返回解析结果
        return [
            $OsName,    // 操作系统名称
            $OsImg,     // 操作系统图标HTML
            $BrowserName, // 浏览器名称
            $BrowserImg   // 浏览器图标HTML
        ];
    }

    /**
     * 生成图标的HTML代码
     *
     * @param  string $type  图标类型路径（os/或browser/）
     * @param  string $name  图标文件名（不含扩展名）
     * @param  string $title 图标标题/提示文本
     * @return string 返回包含base64编码SVG的img标签HTML，如果图标不存在则返回空字符串
     */
    private static function img($type, $name, $title): string
    {
        $size = "18px"; // 图标大小
        $filePath = __DIR__.DS.$type.$name.".svg"; // 构建SVG文件的完整路径

        // 检查文件是否存在
        if (!file_exists($filePath)) {
            return "";
        }

        // 读取SVG文件内容
        $svgContent = file_get_contents($filePath);
        if ($svgContent === false) {
            return "";
        }

        // 将SVG内容转换为base64编码
        $base64 = base64_encode($svgContent);
        // 创建data URI
        $dataUri = 'data:image/svg+xml;base64,' . $base64;

        // 返回完整的img标签HTML
        return "<img nogallery class='icon-ua' src='" . $dataUri . "' title='" . $title . "' alt='" . $title . "' height='" . $size . "' style='vertical-align:-2px;margin-right:0.3rem;margin-left:0.3rem' />";
    }
}
