<?php 
function load_image($image_url)
    {
        $file_extension = explode('.', $image_url);
        $count          = count($file_extension);
        $file_extension = strtolower($file_extension[$count - 1]);
        try {
        switch ($file_extension) {
            case "jpg":
                $image = imagecreatefromjpeg($image_url);
                break;
            case "jpeg":
                $image = imagecreatefromjpeg($image_url);
                break;
            case "bmp":
                $image = imagecreatefromwbmp($image_url);
                break;
            case "png":
                $image = imagecreatefrompng($image_url);
                break;
            case "gif":
                $image = imagecreatefromgif($image_url);
                break;
            case "webp":
                $image = imagecreatefromwebp($image_url);
                break;
            case "default":
                $image = imagecreatefromjpeg($image_url);
                break;
        }
        } catch (Exception $e) {
           echo $e->getMessage();
        }
        return $image;
    }

?>