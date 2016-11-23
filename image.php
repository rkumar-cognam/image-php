<?php



# complete path to active theme.
$image_specif_path = dirname(__FILE__);
$CACHE_IMAGE_DIRECTORY = dirname(__FILE__) ."/output";
$CUT_SIZE = 10;
   /* blended image function that can call Image_Transform class
 to make transformations on images and store it into cache directory. */

image("test_image.jpg", 720, 345, 'crop(smart)');

function image($image_url, $height = 0, $width = 0, $filters = null)
{
    global $CACHE_IMAGE_DIRECTORY;
    global $image_specif_path;

    /* making image url to complete path so that to find image on the server */
    $img_path = $image_specif_path.'/'.$image_url;
    $image_url = realpath($img_path);
   
    if($image_url == false) {
       $image_url = $img_path;
    }

    /* making cache image name to be created and saved with this name. */  
    $save_path = str_replace('/', '_', $image_url);
    $save_path = str_replace('.', '_', $save_path);    
    $save_path = str_replace(':', '_', $save_path);    
    $save_path = str_replace('\\', '_', $save_path);    
    $file_extension = explode('.', $image_url);
    $count = count($file_extension);
    $file_extension = strtolower($file_extension[$count-1]);
    if(!empty($filters)) {
        $save_path = $save_path . '_' . $height . 'x' . $width . '_' . $filters . '.' . $file_extension;
    } else {
        $save_path = $save_path . '_' . $height . 'x' . $width . '.' . $file_extension;
    }
    $save_path = str_replace('#', '', $save_path);    

    // This is the final path to store output images
    $save_path = $CACHE_IMAGE_DIRECTORY . '/' . $save_path;

    /* Applying effects to image, if does not found any image in the cache directory. */  
    if (!file_exists($save_path)) {
        $obj = new TransformImage($image_url, $height, $width);
        
        # applying filters here.
        if(!empty($filters)) {
        if (strpos($filters, "series") != false) {
            $img_obj = $obj->apply_filter($filters);
        } else {
            $filters = str_replace('series(', '', $filters);
            $filters = rtrim($filters, ')');
            $img_obj = $obj->series($filters);
        }
        } else {
            $img_obj = $obj->img;
        }
        # saving image object to cache directory.
        switch ($file_extension) {
            case "jpg":
                imagejpeg($img_obj, $save_path, 100);
                break;
            case "jpeg":
                imagejpeg($img_obj, $save_path, 100);
                break;
            case "bmp":
                imagewbmp($img_obj, $save_path);
                break;
            case "png":
                imagesavealpha($img_obj, true);
                imagepng($img_obj, $save_path, 0);
                break;
            case "gif":
                imagegif($img_obj, $save_path);
                break;
            case "webp":
                imagewebp($img_obj, $save_path);
                break;
            case "default":
                imagejpeg($img_obj, $save_path, '100');
                break;
        }
        # discarding image object after saving it to cache directory.
        imagedestroy($img_obj);
    }

  # Finally getting absolute url of an image to be returned.
  $make_absoute_url = str_replace("/var/www/html", "", $save_path);
  $get_absolute_url = $make_absoute_url;//"http://".$_SERVER['SERVER_NAME'].$make_absoute_url;
  
  return $get_absolute_url;
}

class TransformImage
{
    
    var $width;
    var $height;
    var $orig_w;
    var $orig_h;
    var $img;
    var $FILTER_MAP;
    var $filter;
    var $backend;
    
    function __construct($image, $width, $height)
    {
        $this->FILTER_MAP = array(
            'monochrome' => function()
            {
                return $this->monochrome();
            },
            'alphachrome' => function()
            {
                return $this->alphachrome();
            },
            'warp' => function()
            {
                return $this->warp();
            },
            'fit' => function()
            {
                return $this->fit();
            },
            'fill' => function()
            {
                return $this->fill();
            },
            'resize' => function()
            {
                return $this->resize();
            },
            'crop' => function($crop_type = 'center')
            {
                return $this->crop($crop_type);
            },
            'series' => function()
            {
                return $this->series();
            }
        );
        
        $this->width  = $width;
        $this->height = $height;
        $this->backend = new Backend($image);
        $this->img    = $this->backend->load_image($image);
        $this->orig_w = imagesx($this->img);
        $this->orig_h = imagesy($this->img);
        if ($width == 0) {
            $this->width = $this->orig_w;
        }
        if ($height == 0) {
            $this->height = $this->orig_h;
        }
    }    
    
    # Applying transformation to image in series.
    function series($filter_series)
    {
        $filter_list = explode(',', $filter_series);

        foreach ($filter_list as $filter) {
            $this->filter = $this->filter . '_' . $filter;
            $value        = $this->preg_grep_keys($filter, $this->FILTER_MAP, $flags = 0);

            if ($value['filter'] === $value['filter_arg']) {
                $this->$value['filter']();
            } else {
                $this->$value['filter']($value['filter_arg']);
            }
            
        }
        
        return $this->img;
    }
    
    # Applying single transformation to image.
    function apply_filter($filter)
    {
        $this->filter = $filter;
        $value        = $this->preg_grep_keys($filter, $this->FILTER_MAP, $flags = 0);

        if ($value['filter'] === $value['filter_arg']) {
            $modified_image = $this->$value['filter']();
        } else {
            $modified_image = $this->$value['filter']($value['filter_arg']);
        }
        
        return $this->img;
    }
    
    # Replacing extra characters from filter list.
    function preg_grep_keys($pattern, $input, $flags = 0)
    {
        $filter = preg_replace('/[^a-zA-Z ][(a-zA-Z0-9#)]+/', '', $pattern);
        
        $filter_arg_tmp = preg_replace('/' . $filter . '[(]+/', '', $pattern);
        $filter_arg     = preg_replace("/[)]/", "", $filter_arg_tmp);
        
        $filter_and_type = array(
            'filter' => trim($filter),
            'filter_arg' => trim($filter_arg)
        );
        return $filter_and_type;
    }
    
    # applying monochrome effect here.
    function monochrome()
    {
        imagefilter($this->img, IMG_FILTER_GRAYSCALE);
        imagefilter($this->img, IMG_FILTER_CONTRAST, -255);
    }

    # Alphachrome is a blended term which applies colors to png images.
    function alphachrome($hex = '#3377FF')
    {   
        $hex = str_replace("#", "", $hex);
        if (strlen($hex) == 3) {
            $hex_r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $hex_g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $hex_b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $hex_r = hexdec(substr($hex, 0, 2));
            $hex_g = hexdec(substr($hex, 2, 2));
            $hex_b = hexdec(substr($hex, 4, 2));
        }
        
        $newColor = array(
            $hex_r,
            $hex_g,
            $hex_b
        );
        
        // Work through pixels
        for ($y = 0; $y < $this->orig_h; $y++) {
            for ($x = 0; $x < $this->orig_w; $x++) {
                // Apply new color + Alpha
                $rgb = imagecolorsforindex($this->img, imagecolorat($this->img, $x, $y));
                
                $transparent = imagecolorallocatealpha($this->img, 0, 0, 0, 127);
                imagesetpixel($this->img, $x, $y, $transparent);
                
                
                // Here, you would make your color transformation.
                $red_set   = $newColor[0];
                $green_set = $newColor[1];
                $blue_set  = $newColor[2];
                if ($red_set > 255)
                    $red_set = 255;
                if ($green_set > 255)
                    $green_set = 255;
                if ($blue_set > 255)
                    $blue_set = 255;
                
                $pixelColor = imagecolorallocatealpha($this->img, $red_set, $green_set, $blue_set, $rgb['alpha']);
                imagesetpixel($this->img, $x, $y, $pixelColor);
            }
        }
        
        // Restore Alpha
        imageAlphaBlending($this->img, true);
        imageSaveAlpha($this->img, true);
        
        /*** thumb updation starts here  ***/
        
        $bottom = FALSE;
        $width  = imagesx($this->img);
        $height = imagesy($this->img);
        
        $thumbHeight = $bottom != FALSE ? $height * 2 : $height;
        
        // Create Transparent PNG
        $thumb       = imagecreatetruecolor($width, $thumbHeight);
        $transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
        imagefill($thumb, 0, 0, $transparent);
        
        // Copy Top Image
        imagecopy($thumb, $this->img, 0, 0, 0, 0, $width, $height);
        
        // Copy Bottom Image
        if ($bottom != FALSE) {
            imagecopy($thumb, $bottom, 0, $height, 0, 0, $width, $height);
        }
        
        // Save Image with Alpha
        imageAlphaBlending($thumb, true);
        imageSaveAlpha($thumb, true);
        
        $this->img = $thumb;
    }
    
    # applies wrap effect to image.
    function warp()
    {
        $ratio_w = ($this->width)/($this->orig_w);
        $ratio_h = ($this->height)/($this->orig_h);

        $thumb = imagecreatetruecolor($this->orig_w*$ratio_w, $this->orig_h*$ratio_h);
        imagecopyresized($thumb, $this->img, 0, 0, 0, 0, $this->orig_w*$ratio_w, $this->orig_h*$ratio_h, $this->orig_w, $this->orig_h);
        $this->img = $thumb;
    }
 
    # fit image with the given canvas.
    function fit()
    {
        if ($this->orig_w / $this->width > $this->orig_h / $this->height) {
            $ratio = $this->width / $this->orig_w;
        } else {
            $ratio = $this->height / $this->orig_h;
        }
        $thumb = imagecreatetruecolor($this->orig_w * $ratio, $this->orig_h * $ratio);
        imagecopyresized($thumb, $this->img, 0, 0, 0, 0, $this->orig_w * $ratio, $this->orig_h * $ratio, $this->orig_w, $this->orig_h);
        $this->img = $thumb;
    }

    # fill image with the given canvas.
    function fill()
    {
        if ($this->orig_w / $this->width < $this->orig_h / $this->height) {
            $ratio = $this->width / $this->orig_w;
        } else {
            $ratio = $this->height / $this->orig_h;
        }
        $thumb = imagecreatetruecolor($this->orig_w * $ratio, $this->orig_h * $ratio);
        imagecopyresized($thumb, $this->img, 0, 0, 0, 0, $this->orig_w * $ratio, $this->orig_h * $ratio, $this->orig_w, $this->orig_h);
        $this->img = $thumb;
    }

    # resize height, width of image with the new canvas size.
    function resize($percent = 100)
    {
        
        if ($this->orig_w / $this->width < $this->orig_h / $this->height) {
            $diff  = abs($this->width - $this->orig_w) * $percent / 100.0;
            $ratio = ($this->width + $diff) / $this->orig_w;
        } else {
            $diff  = abs($this->height - $this->orig_h) * $percent / 100.0;
            $ratio = ($this->height + $diff) / $this->orig_h;
        }
        
        $thumb = imagecreatetruecolor($this->orig_w * $ratio, $this->orig_h * $ratio);
        imagecopyresized($thumb, $this->img, 0, 0, 0, 0, $this->orig_w * $ratio, $this->orig_h * $ratio, $this->orig_w, $this->orig_h);
        $this->img = $thumb;
    }

    # crop a portion of the image.
    function crop($crop_type = 'center')
    {
        global $CUT_SIZE;
        // builds cropbox size of bounding box in center of image
        if ($crop_type == 'center') {

            $thumb_im = imagecreatetruecolor($this->width, $this->height);
            if($this->orig_w > $this->width)
            {
             

                $crop_w   = ($this->orig_w - $this->width) / 2;
                $crop_h   = ($this->orig_h - $this->height) / 2;

                #die();

                if($this->orig_h < $this->height) {
                    $crop_h   = ($this->height - $this->orig_h) / 2;
                }

                $cropbox  = array(
                    'x' => $crop_w,
                    'y' => $crop_h,
                    'width' => ($crop_w + $this->width),
                    'height' => ($crop_h + $this->height)
                );
                echo $this->orig_w . "--" . $this->width . "--" . $crop_w . "--" . $cropbox['width'];

                echo "<br />";     
                echo $this->orig_h . "--" . $this->height . "--" . $crop_h . "--" . $cropbox['height'];

                imagecopy($thumb_im, $this->img, 0, 0, $crop_w, $crop_h, $cropbox['width'], $cropbox['height']);
            } else {
                $crop_w   = ($this->width - $this->orig_w) / 2;
                $crop_h   = ($this->height - $this->orig_h) / 2;

                if($this->orig_h > $this->height) {
                    $crop_h   = ($this->orig_h - $this->height) / 2;
                }

                $cropbox  = array(
                    'x' => $crop_w,
                    'y' => $crop_h,
                    'width' => ($crop_w + $this->width),
                    'height' => ($crop_h + $this->height)
                );
                imagecopy($thumb_im, $this->img, 0, 0, 0, $cropbox['height'], $cropbox['width'], $cropbox['height']);
            }
        


        }
        // builds cropbox size of bounding box on top left of image
        else if ($crop_type == 'top-left') {
            $cropbox  = array(
                'x' => 0,
                'y' => 0,
                'width' => $this->width,
                'height' => $this->height
            );
            $thumb_im = imagecreatetruecolor($this->width, $this->height);
            imagecopy($thumb_im, $this->img, 0, 0, 0, 0, $this->width, $this->height);
        }
        // builds cropbox size of bounding box on bottom right of image
        else if ($crop_type == 'bottom-right') {

            $thumb_im = imagecreatetruecolor($this->width, $this->height);


            if($this->orig_w > $this->width) {
                $cropbox  = array(
                    'x' => $this->orig_w - $this->width,
                    'y' => $this->orig_h - $this->height,
                    'width' => $this->width,
                    'height' => $this->height
                );
                if($this->orig_h < $this->height) {
                   // $cropbox['y']   = ($this->height - $this->orig_h);
                }
                imagecopy($thumb_im, $this->img, 0, 0, $cropbox['x'],$cropbox['y'], $cropbox['width'], $cropbox['height']);

            } else {
                $cropbox  = array(
                    'x' => $this->width - $this->orig_w,
                    'y' => $this->height - $this->orig_h,
                    'width' => $this->width,
                    'height' => $this->height
                );
               imagecopy($thumb_im, $this->img, 0, 0, 0, $cropbox['width']-$cropbox['height'], $cropbox['width'], $cropbox['height']);
            }
            
        }
        // does a smart crop
        else if ($crop_type == 'smart') {
           
         
            $wdiff = $this->orig_w - $this->width;
            $hdiff = $this->orig_h - $this->height;
           
           $thumb_im = imagecreatetruecolor($this->width, $this->height);
            while ($wdiff > 0 || $hdiff > 0) {
                print_r($wdiff);print_r("X");print_r($hdiff);
                echo "<br />";
                 


                if ($wdiff >= $hdiff) {
                    $slice_width =  (int) min($wdiff, $CUT_SIZE);
                    #left = self.image.crop((0,0,slice_width,int(self.h)))
                    #right = self.image.crop((int(self.w-slice_width),0,int(self.w),int(self.h)))
                     $thumb_im1 = imagecreatetruecolor($slice_width, $this->orig_h);
                     $thumb_im2 = imagecreatetruecolor($slice_width, $this->orig_h);

                    $a =  array(0, 0, $slice_width, $this->orig_h);
                    $b =  array(($this->orig_w - $slice_width), 0, $this->orig_w,  $this->orig_h);

                    echo ("_____________________________________________");
                    echo ( "<br />" );
                    print_r($a);
                    echo ( "<br />" );
                    echo ("_____________________________________________");
                    echo ("<br />");
                    print_r($b);

                    echo ("<br />");


                    $left =  imagecopy($thumb_im1, $this->img,0, 0, 0, 0, $slice_width, $this->orig_h);
                    $right =  imagecopy($thumb_im2, $this->img,0, 0, ($this->orig_w - $slice_width), 0, $this->orig_w,  $this->orig_h);
                   
                    echo ("thumb_im1   " . $this->__entropy($thumb_im1) . "<br />");
                    echo ("thumb_im2   " . $this->__entropy($thumb_im2) . "<br />");
           #$this->img = $thumb_im1; return;
                    print_r($left);
                    if ($this->__entropy($thumb_im1) > $this->__entropy($thumb_im2) ) {
                        echo "if";
                         $cropbox  = array(
                                            'x' => 0,
                                            'y' => 0,
                                            'width' => $this->orig_w - slice_width,
                                            'height' => $this->orig_h
                                        );
                         #(0,0,int(self.w-slice_width),int(self.h))
                          imagecopy($thumb_im, $this->img, 0, 0, $cropbox ['x'], $cropbox ['y'], $cropbox ['width'], $cropbox ['height']);
                    } else {
                        echo "else";
                        echo "<br />";
                        echo $slice_width;
                         $cropbox  = array(
                                            'x' => $slice_width,
                                            'y' => 0,
                                            'width' => $this->orig_w,
                                            'height' => $this->orig_h
                                        );
                          #(slice_width,0,int(self.w),int(self.h))
                          imagecopy($thumb_im, $this->img, 0, 0, $cropbox ['x'], $cropbox ['y'], $cropbox ['width'], $cropbox ['height']);
                    }                    
                } else {
                    echo "string"; 
                }
                $this->img = $thumb_im;
                list( $tmp_w, $tmp_h ) =  array(imagesx($this->img), imagesy($this->img));
                print_r("<br />");
                print_r("<br />");
                print_r("<br />");
                print_r("<br />");
                print_r("<br />");
                print_r("<br />");
                print_r("<br />");
                print_r("<br />");
                print_r("<br />");
                print_r($tmp_w);
                $this->orig_w =  $tmp_w;
                $this->orig_h =  $tmp_h;
                $wdiff = $this->orig_w - $this->width;
                $hdiff = $this->orig_h - $this->height;
            }

            // $cropbox      = array(
            //     'x' => 0,
            //     'y' => 0,
            //     'width' => $this->width,
            //     'height' => $this->height
            // );
            // $thumb_im = imagecreatetruecolor($this->width, $this->height);
            // imagecopy($thumb_im, $this->img, 0, 0, 0, $cropbox['height'], $cropbox['width'], $cropbox['height']);

        }
       # $this->img = $thumb_im;
    }

    function __entropy ($image= null) {
        if (!$image) {
            $image =  $this->img;
        }

        
    #list($width, $height, $imtype) = getimagesize($image);

        $image_file = $image;
        $im = $image;
        $org_size = array(imagesx($image), imagesy($image));
        #$imgw = imagesx($image);
        #$imgh = imagesy($image);

        list($width, $height) = $org_size;
        $imtype =  'IMAGETYPE_JPEG';

        #print_r($imgw);
        // n = total number or pixels

            # Initialize the histogram counters:
            $histogram = array_fill(0, 256, 0);

            # Process every pixel. Get the color components and compute the gray value.
            for ($y = 0; $y < $height; $y++) {
                for ($x = 0; $x < $width; $x++) {
                    $pix = imagecolorsforindex($im, imagecolorat($im, $x, $y));
                    $value = (int)((30 * $pix['red'] + 59 * $pix['green']
                                  + 11 * $pix['blue']) / 100);
                    $histogram[$value]++;
                }
            }

            //print_r(json_encode($histogram));
        return array_sum ($histogram);

        // die();

        // $n = $imgw*$imgh;
        // #$hist = getImageHistogram($image);
        // $hist = array();

        // for ($i=0; $image; $i++)
        // {
        //         for ($j=0; $image; $j++)
        //         {
                
        //                 // get the rgb value for current pixel
                        
        //                 $rgb = ImageColorAt($im, $i, $j); 
                        
        //                 // extract each value for r, g, b
                        
        //                 $r = ($rgb >> 16) & 0xFF;
        //                 $g = ($rgb >> 8) & 0xFF;
        //                 $b = $rgb & 0xFF;
                        
        //                 // get the Value from the RGB value
                        
        //                 $V = round(($r + $g + $b) / 3);
                        
        //                 if (!$V) {
        //                     echo "djhdfdjffdj";
        //                 }
        //                 // add the point to the histogram
        //                 else {
        //                     $hist[$V] += $V / $n;
        //                 }
        //         }
        // }
        // print_r($hist); die();
     }
    
}

/**
 * Compute the entropy of an image, defined as -sum(p.*log2(p)).
 * @param resource $img GD image resource.
 * @return float The entropy of the image.
 */
function _smartcrop_gd_entropy($img) {
  $histogram = _smartcrop_gd_histogram($img);
  $histogram_size = array_sum($histogram);
  $entropy = 0;
  foreach ($histogram as $p) {
    if ($p == 0) {
      continue;
    }
    $p = $p / $histogram_size;
    $entropy += $p * log($p, 2);
  }
  return $entropy * -1;
}

/**
 * Compute a histogram of an image.
 * @param resource $img GD image resource.
 * @return array histogram as an array.
 */
function _smartcrop_gd_histogram($img) {
  $histogram = array_fill(0, 768, 0);
  for ($i = 0; $i < imagesx($img); $i++) {
    for ($j = 0; $j < imagesy($img); $j++) {
      $rgb = imagecolorat($img, $i, $j);
      $r = ($rgb >> 16) & 0xFF;
      $g = ($rgb >> 8) & 0xFF;
      $b = $rgb & 0xFF;
      $histogram[$r]++;
      $histogram[$g + 256]++;
      $histogram[$b + 512]++;
    }
  }
  return $histogram;
}


function image_gd_create_tmp(stdClass $image, $width, $height) {
  $res = imagecreatetruecolor($width, $height);

  if ($image->info['extension'] == 'gif') {
    // Find out if a transparent color is set, will return -1 if no
    // transparent color has been defined in the image.
    $transparent = imagecolortransparent($image->resource);

    if ($transparent >= 0) {
      // Find out the number of colors in the image palette. It will be 0 for
      // truecolor images.
      $palette_size = imagecolorstotal($image->resource);
      if ($palette_size == 0 || $transparent < $palette_size) {
        // Set the transparent color in the new resource, either if it is a
        // truecolor image or if the transparent color is part of the palette.
        // Since the index of the transparency color is a property of the
        // image rather than of the palette, it is possible that an image
        // could be created with this index set outside the palette size (see
        // http://stackoverflow.com/a/3898007).
        $transparent_color = imagecolorsforindex($image->resource, $transparent);
        $transparent = imagecolorallocate($res, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);

        // Flood with our new transparent color.
        imagefill($res, 0, 0, $transparent);
        imagecolortransparent($res, $transparent);
      }
      else {
        imagefill($res, 0, 0, imagecolorallocate($res, 255, 255, 255));
      }
    }
  }
  elseif ($image->info['extension'] == 'png') {
    imagealphablending($res, FALSE);
    $transparency = imagecolorallocatealpha($res, 0, 0, 0, 127);
    imagefill($res, 0, 0, $transparency);
    imagealphablending($res, TRUE);
    imagesavealpha($res, TRUE);
  }
  else {
    imagefill($res, 0, 0, imagecolorallocate($res, 255, 255, 255));
  }

  return $res;
}

class Backend
{
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

}
?>

