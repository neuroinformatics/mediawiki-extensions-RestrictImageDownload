<?php

declare(strict_types=1);

namespace MediaWiki\Extension\RestrictImageDownload;

class ImageFile
{
    private const SUPPORTED_MIME_TYPES = [
        'image/gif' => 'imagecreatefromgif',
        'image/png' => 'imagecreatefrompng',
        'image/jpeg' => 'imagecreatefromjpeg',
    ];
    private const BLOCK_SIZE = 4096 * 1024;
    private const RESTRICTED_IMAGE = <<<EOT
iVBORw0KGgoAAAANSUhEUgAAAIAAAACABAMAAAAxEHz4AAAAG1BMVEUAAAD/SEj/Skr/Skr/Skr/
SUn/Skr/Skr/TEy0Z1w1AAAACXRSTlMAIGB/n7/f/0COuirkAAADiUlEQVR4AayZv0/bQBzF7abQ
jIaKhjFCSDB2gIYxgiEdU6mRV1cpkDGJ7ftmvLKkf3aXyE/3hM/PlO9EON+7z/fXnXVO2i09vVk8
mXv8+XCdvMHObw3m7n3P6emtkS17SXzGRFB815f/FswERCbOn1mL/fby/BarvDA/t4hVmb5+ixdd
ChPrsGV8/hfrtF+x+QPrNvdVC2BEoT0Me5NsKTgQtzYnclWgfH3+kZE9PVz/SU7OLy5Z2aZKBB/v
MPSSC3E8Cx+5C9UvI8UAgFiyT/MoAkWgzrq6pIimoMw6g1TFAJwXCpV9nCjFPmivhaHB5tpmOw5G
RizdVWucyZwCIBR73eJcoQFQGPekK3Xb6tWRuQxgjj1QAFp8GOkA7ANJOxGAynnYNwWccQyJNcAO
79UiZNux9lwDWFDOEAINoE5DlzG2jQOAcxIif8JPCQBH8KESrgCkASTDMOrPqAsNAHouiOFGAzjU
PqA/4rySAPCPg9wx1FQAUBeIoVU6ACR32NBtqwNg1RJJsLUCgB0EaUAMdQBEPks+IIY6AKLooaUA
4JjEvniEJOgAkJ0nx0iCDoDcFU0vbjQAPorW+EsD4DzukisM6wAYKBtnpiIAFUKZzHBO6QA4Datm
jbEAEBXwvQCaAq6bXsp0ANQyCegAEHAQEAGoGVzTzTIAdRMJqADYRuCCABAV8CIAxwB1IABEBcYy
ANUBekEHQCWSgA6AXkA7ywDUztjc+gBgK6UtTQXAVorNTQSgTXWDzU0EoNN13TxcKwCwWTM+wDkr
AwRHMo5JHQB1ZGM09rQPAI5krFfoAMhiRa84GkDwYoWMOhGAXrJAY14GQOTX9NIoAdDLKdRWIgC9
6mLNSgVAHVp4deBFAEBvQ55CBMAym/BnKQCgClC9Ke52NIDEwsyDeyEBgLPmpJgEgKjvWFEDGBqv
kqoAfO/quS70ewh6aqQBsMMbPqd0AD4N0/wtAI67qy/AjjZZDeDMyAPSVWsAj8EHvQg5B+zDD8kB
8+95IUljbixcic55cBK/VOVH6qh7VvmOa2EAYPw5fjE9COa7zq8j7j767WulXc5jOsYIgBchiou/
WXrycpML7Y4oC1a//ycSHBiKrdTPRLoDuhNwQGg33QE9E+hCXUEPgKTg/P99MnX/RuGka6kAjaeN
EaAFm/Mt6DJ1jgASyHN8ZcEkaUcsH3ANSy9LC8W7fAAA4RaJLC6haOoAAAAASUVORK5CYII=
EOT;
    private const RESTRICTED_IMAGE_SIZE = 128;

    private $file_path;
    private $file_size;
    private $mime_type;
    private $image_size;

    public function __construct(string $file_path)
    {
        $this->file_path = $file_path;
        $this->file_size = @filesize($file_path);
        $this->mime_type = $this->get_mime_type($file_path);
        $this->image_size = @getimagesize($file_path);
    }

    public function validate(): bool
    {
        return in_array($this->mime_type, array_keys(self::SUPPORTED_MIME_TYPES)) && false !== $this->image_size;
    }

    public function output()
    {
        $this->output_common_header();
        header('Content-Type: '.$this->mime_type);
        header('Content-Length: '.$this->file_size);
        $file_handle = fopen($this->file_path, 'rb');
        while (!feof($file_handle)) {
            echo fread($file_handle, self::BLOCK_SIZE);
            flush();
        }
        fclose($file_handle);
    }

    public function output_protected_image()
    {
        list($w, $h) = $this->image_size;
        $im = imagecreatetruecolor($w, $h);
        imageantialias($im, true);
        imagesavealpha($im, true);
        imagealphablending($im, true);
        $col_blur = imagecolorallocatealpha($im, 255, 255, 255, 30);
        $col_white = imagecolorallocate($im, 255, 255, 255);
        $col_red = imagecolorallocate($im, 255, 0, 0);
        imagefill($im, 0, 0, $col_white);
        $im_base = self::SUPPORTED_MIME_TYPES[$this->mime_type]($this->file_path);
        imagecopy($im, $im_base, 0, 0, 0, 0, $w, $h);
        imagedestroy($im_base);
        imagefilledrectangle($im, 0, 0, $w, $h, $col_blur);
        $im_protect = imagecreatefromstring(base64_decode(self::RESTRICTED_IMAGE));
        $p_size = intval(min(min($w, $h) * 0.7, self::RESTRICTED_IMAGE_SIZE));
        imagecopyresampled($im, $im_protect, intval($w / 2 - $p_size / 2), intval($h / 2 - $p_size / 2), 0, 0, $p_size, $p_size, self::RESTRICTED_IMAGE_SIZE, self::RESTRICTED_IMAGE_SIZE);
        imagedestroy($im_protect);
        $this->output_common_header();
        header('Content-Type: image/png');
        imagepng($im);
        imagedestroy($im);
    }

    private function get_mime_type(string $file_path): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_path);
        finfo_close($finfo);

        return false !== $mime_type ? preg_replace('/\s+;.*$/', '', $mime_type) : '';
    }

    private function output_common_header()
    {
        while (0 !== ob_get_level()) {
            ob_end_clean();
        }
        header('Expires: Tue, 03 Jul 2001 06:00:00 GMT');
        header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
    }
}
