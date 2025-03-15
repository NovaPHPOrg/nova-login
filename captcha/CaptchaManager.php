<?php

declare(strict_types=1);

namespace nova\plugin\login\captcha;

use nova\framework\exception\AppExitException;
use nova\framework\http\Response;
use nova\plugin\cookie\Session;

class CaptchaManager
{
    private const SESSION_KEY = 'captcha_code';
    private const DEFAULT_LENGTH = 4;
    private const DEFAULT_WIDTH = 150;
    private const DEFAULT_HEIGHT = 40;
    
    private int $length;
    private int $width;
    private int $height;
    
    public function __construct(int $length = self::DEFAULT_LENGTH, 
                              int $width = self::DEFAULT_WIDTH, 
                              int $height = self::DEFAULT_HEIGHT)
    {
        $this->length = $length;
        $this->width = $width;
        $this->height = $height;
    }
    
    /**
     * 生成验证码图片
     */
    public function generate(): void
    {
        // 创建画布
        $image = imagecreatetruecolor($this->width, $this->height);
        
        // 设置背景色
        $bgColor = imagecolorallocate($image, 246, 246, 246);
        imagefill($image, 0, 0, $bgColor);
        
        // 生成随机字符
        $code = $this->generateRandomCode();
        
        // 保存验证码到session
        Session::getInstance()->set(self::SESSION_KEY, strtolower($code));
        
        // 添加干扰线
        $this->addInterferenceLines($image);
        
        // 添加噪点
        $this->addNoise($image);
        
        // 写入文字
        $this->writeText($image, $code);
        
        // 输出图片
        ob_start();
        imagepng($image);
        imagedestroy($image);
        throw new AppExitException(Response::asRaw(ob_get_clean(),['Content-Type'=>'image/png']));
    }
    
    /**
     * 验证验证码
     */
    public function validate(?string $code): bool
    {
        if ($code === null) {
            return false;
        }
        
        $savedCode = Session::getInstance()->get(self::SESSION_KEY);
        if ($savedCode === null) {
            return false;
        }
        
        // 验证完后立即删除，防止重复使用
        Session::getInstance()->delete(self::SESSION_KEY);
        
        return strtolower($code) === strtolower($savedCode);
    }
    
    /**
     * 生成随机验证码
     */
    private function generateRandomCode(): string
    {
        // 验证码字符集，不包含容易混淆的字符
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        $code = '';
        
        for ($i = 0; $i < $this->length; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $code;
    }
    
    /**
     * 添加干扰线
     */
    private function addInterferenceLines($image): void
    {
        for ($i = 0; $i < 6; $i++) {
            $lineColor = imagecolorallocate($image, 
                random_int(50, 100), 
                random_int(50, 100), 
                random_int(50, 100)
            );
            
            imageline($image,
                random_int(0, $this->width),
                random_int(0, $this->height),
                random_int(0, $this->width),
                random_int(0, $this->height),
                $lineColor
            );
        }
    }
    
    /**
     * 添加噪点
     */
    private function addNoise($image): void
    {
        for ($i = 0; $i < 100; $i++) {
            $noiseColor = imagecolorallocate($image,
                random_int(50, 100),
                random_int(50, 100),
                random_int(50, 100)
            );
            
            imagesetpixel($image,
                random_int(0, $this->width),
                random_int(0, $this->height),
                $noiseColor
            );
        }
    }
    
    /**
     * 写入文字
     */
    private function writeText($image, string $code): void
    {
        $fontFile = __DIR__ . '/fonts/roboto.ttf';
        $fontSize = $this->height * 0.6;
        
        // 计算每个字符的位置
        $length = strlen($code);
        $charWidth = $this->width / ($length + 2);
        
        for ($i = 0; $i < $length; $i++) {
            $textColor = imagecolorallocate($image,
                random_int(30, 70),
                random_int(30, 70),
                random_int(30, 70)
            );
            
            // 随机倾斜角度
            $angle = random_int(-20, 20);
            
            // 计算文字位置
            $x = ($i + 1) * $charWidth;
            $y = $this->height * 0.7;
            
            imagettftext($image, 
                $fontSize, 
                $angle, 
                (int)$x, 
                (int)$y, 
                $textColor, 
                $fontFile, 
                $code[$i]
            );
        }
    }
} 