<?php
include 'load_image.php';
// dirname(__FILE__) C:\xampp\htdocs\www
# path to image cache directory.
$CACHE_DIR = dirname(__FILE__);
$image_specif_path = dirname(__FILE__);


function image($image_url,  $options = array())
{
	#$height = 0, $width = 0, $filters = null
	if(isset($options["height"])) {
	    $height = $options["height"];
	} else {
		$height = null;
	}

	if(isset($options["width"])) {
	    $width = $options["width"];
	} else {
		$width = null;
	}

	if(isset($options["filter"])) {
	    $filter = $options["filter"];
	} else {
		$filter = null;
	}

	$image_url  =  realpath ($image_url);
	/* making cache image name to be created and saved with this name. */  
    $save_path = str_replace('/', '_', $image_url);
    $save_path = str_replace('.', '_', $save_path);    
    $file_extension = explode('.', $image_url);
    $count = count($file_extension);
    $file_extension = strtolower($file_extension[$count-1]);
    echo($image_url);
    echo "<br />";
    echo($save_path);
    echo "<br />";
    if ($height) {
    	$save_path =  $save_path . '_' . $height;
    }

    if ($width) {
    	$save_path =  $save_path . 'x' . $width;
    }

    if ($filter) {
    	$save_path =  $save_path . '_' . $filter;
    }

    if ($file_extension) {
		$save_path =  $save_path . '.' . $file_extension;    
	}

	#echo($save_path);

	if (!file_exists($save_path)) { 
		$obj = new TransformImage ($image_url,  $height, $width); 
		if (!$filter) {
			$filter = "series";
		}

		$pos = strpos($filter, 'series'); 
		$img_obj; #declaring it here , for variable scope  
		#check the string start with series
		if ($pos === 0) {
			 $img_obj = $obj->apply_filter($filter);
		}
	}
	# Todo hai yahan se
	#global $CACHE_DIR;
    #global $image_specif_path;
    /* making image url to complete path so that to find image on the server */
    /*
    $save_path = dirname(__FILE__);
    $file_extension = explode('.', $image_url);
    #print_r ($file_extension);
    $count = count($file_extension);
    $file_name =  strtolower($file_extension[$count-2]);
    $file_extension = strtolower($file_extension[$count-1]);
    #print_r ($file_extension);
    #print($save_path);
    #echo($file_name);
    if(!empty($filters)) {
        $save_path = $save_path . '/' . $file_name . '_' . $height . 'x' . $width . '_' . $filters . '.' . $file_extension;
    } else {
        $save_path = $save_path . '/' . $file_name . '_' . $height . 'x' . $width . '.' . $file_extension;
    }
    $save_path = str_replace('#', '', $save_path);
    #$save_path = $CACHE_DIR . '/' . $save_path;
    #print_r($save_path);
    /* Applying effects to image, if does not found any image in the cache directory. */  
    /*
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
    $get_absolute_url = $save_path;
    return $get_absolute_url;
    */
}




class TransformImage
{
    
    var $width;
    var $height;
    var $orig_w = 440;
    var $orig_h = 440;
    var $img;
    var $FILTER_MAP;
    var $filter;
    var $backend;
    function __construct($image, $width =null, $height =null)
    {
    	
    	$this->FILTER_MAP = array(
    		'fit' => function()
            {
                return $this->fit();
            },
            'fill' => function()
            {
                return $this->fill();
            },
            'crop' => function($crop_type = 'center')
            {
                return $this->crop($crop_type);
            }
    		);
    	$this->img    = load_image ($image);
    	#Start of format
    	$file_extension = explode('.', $image);
        $count          = count($file_extension);
        $file_extension = strtolower($file_extension[$count - 1]);
    	$this->format = $file_extension;  
    	#end of format
    	if ($file_extension=='gif' || $file_extension == 'GIF') {
    		$this->transparency = imagecolortransparent($this->img);
    	}
    	else {
    		$this->transparency = null;
    	}
    	$this->w = imagesx ($this->img) * 1.0;
    	$this->h = imagesy($this->img) * 1.0;


    	#$this->orig_w = imagesx($this->img);
        #$this->orig_h = imagesy($this->img);
    	if ($width) {
            $this->width = $width * 1.0;
        } else {
        	$this->width = $this->w;
        }

        if ($height == 0) {
            $this->height = $this->orig_h;
        } else {
        	$this->width = $this->h;
        }

    }


     # Applying single transformation to image.
    function apply_filter($filter)
    {
    	#m = re.match('(\w+)(\((.*)\))?$', filter)
    	//echo($filter);
    	preg_match("/(\w+)(\((.*)\))?$/", $filter, $matches);
		$filter_name  = $matches[1];
		echo($filter_name);
		$args =  array();
		if (isset($matches[3])) {
			$args = explode(",", $matches[3]);
		}
		print_r($args);
		$clean_args  =  array();
		foreach($array as $key=>$value){
		 print "$key holds $value\n";
		}





		
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


        # fit image with the given canvas.
    /*
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
    */
    # fill image with the given canvas.
    function fill()
    {
        // if ($this->orig_w / $this->width < $this->orig_h / $this->height) {
        //     $ratio = $this->width / $this->orig_w;
        // } else {
        //     $ratio = $this->height / $this->orig_h;
        // }
        // $thumb = imagecreatetruecolor($this->orig_w * $ratio, $this->orig_h * $ratio);
        // imagecopyresized($thumb, $this->img, 0, 0, 0, 0, $this->orig_w * $ratio, $this->orig_h * $ratio, $this->orig_w, $this->orig_h);
        // $this->img = $thumb;
    }

    function crop($crop_type = 'center')
    {

    }


     # Applying transformation to image in series.
    function series($filter_series)
    {
        $filter_list = explode(',', $filter_series);
        foreach ($filter_list as $filter) {
            $this->filter = $this->filter . '_' . $filter;
            $value        = $this->preg_grep_keys($filter, $this->FILTER_MAP, $flags = 0);
            if ($value['filter'] === $value['filter_arg']) {
                call_user_func(array($this, $value['filter']));
            } else {
                call_user_func(array ($this, $value['filter']) , $value['filter_arg']);
            }
            
        }
        
        return $this->img;
    }
}

$op = image ("birds_anim.gif",array('width' => 450,  'height' => 719, 'filter' => 'series(crop)'));
echo "</br>";
echo "</br>";
echo "<br />";
echo "<hr />";
echo "<br />";
#print($op)
?>