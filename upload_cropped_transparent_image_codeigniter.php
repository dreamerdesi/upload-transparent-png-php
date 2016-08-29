<?php





/**
     * Upload a cropped transparent image and push to Amazon S3 
     *
     * @param string $name
     * @return mixed
     */
    private function uploadCroppedandTransparent($name='image'){


        $year = date('Y');
        $month = date('m');
        // a custom extension defined
        $ratio = "@3x";

        // randomized extension

        $filename = md5(time().rand(0.1, 99.9)).$ratio;
   

        $fileinfo = $_FILES[$name];

        if (!$fileinfo['size']){
            $this->controller->data['show_errors'][] = "Please select a photo file.";
            return false;
        }

        $ext = strtolower(pathinfo($fileinfo['name'], PATHINFO_EXTENSION));

        if(stripos("jpg, png, bmp, jpeg, gif", $ext) === false){
            $this->controller->data['show_errors'][] = "Only jpg, png, gif, bmp files can be uploaded.";
            return false;
        }

        $filepath             = UPLOAD_DIR."photo/".$year."/".$month;
        $filepath_big_thumb   = UPLOAD_DIR."big_thumb/photo/".$year."/".$month;
        $filepath_thumb       = UPLOAD_DIR."thumb/photo/".$year."/".$month;
        $filepath_small_thumb = UPLOAD_DIR."small_thumb/photo/".$year."/".$month;

        $file_a3_path             = UPLOAD_AMZ."photo/".$year."/".$month."/".$filename.".".$ext;
        $file_a3_path_big_thumb   = UPLOAD_AMZ."big_thumb/photo/".$year."/".$month."/".$filename.".".$ext;
        $file_a3_path_thumb       = UPLOAD_AMZ."thumb/photo/".$year."/".$month."/".$filename.".".$ext;
        $file_a3_path_small_thumb = UPLOAD_AMZ."small_thumb/photo/".$year."/".$month."/".$filename.".".$ext;

        if(!is_dir($filepath)){
            mkdir($filepath, 0777, true);
        }

        if(!is_dir($filepath_big_thumb)){
            mkdir($filepath_big_thumb, 0777, true);
        }

        if(!is_dir($filepath_thumb)){
            mkdir($filepath_thumb, 0777, true);
        }

        if(!is_dir($filepath_small_thumb)){
            mkdir($filepath_small_thumb, 0777, true);
        }

        $filepath             .= "/".$filename.".".$ext;
        $filepath_big_thumb .= "/".$filename.".".$ext;
        $filepath_thumb       .= "/".$filename.".".$ext;
        $filepath_small_thumb .= "/".$filename.".".$ext;

        //Creating Photo
        $source = $this->loadImage($fileinfo['tmp_name']);

        list($ow, $oh) = getimagesize($fileinfo['tmp_name']);
        $newwidth = 114;
        $newheight=($oh/$ow)*114;
        
     
        // image coords with the value of 'image'
        if (isset($_REQUEST['image'])){
            $rw = isset($_REQUEST['photo_width']) ? $_REQUEST['photo_width'] : $ow;
            $rh = isset($_REQUEST['photo_height']) ? $_REQUEST['photo_height'] : $oh;
            $x1 = isset($_REQUEST['photo_crop_x1']) ? $_REQUEST['photo_crop_x1'] : 0;
            $y1 = isset($_REQUEST['photo_crop_y1']) ? $_REQUEST['photo_crop_y1'] : 0;
            $x2 = isset($_REQUEST['photo_crop_x2']) ? $_REQUEST['photo_crop_x2'] : $rw;
            $y2 = isset($_REQUEST['photo_crop_y2']) ? $_REQUEST['photo_crop_y2'] : $rh;
            $cw = isset($_REQUEST['photo_crop_w']) ? $_REQUEST['photo_crop_w'] : $rw;
            $ch = isset($_REQUEST['photo_crop_h']) ? $_REQUEST['photo_crop_h'] : $rh;
        }else { // All other image coords

            $rw = isset($_REQUEST['photo_width']) ? $_REQUEST['photo_width'] : $ow;
            $rh = isset($_REQUEST['photo_height']) ? $_REQUEST['photo_height'] : $oh;
            $x1 = isset($_REQUEST['photo_crop_x1']) ? $_REQUEST['photo_crop_x1'] : 0;
            $y1 = isset($_REQUEST['photo_crop_y1']) ? $_REQUEST['photo_crop_y1'] : 0;
            $x2 = isset($_REQUEST['photo_crop_x2']) ? $_REQUEST['photo_crop_x2'] : $rw;
            $y2 = isset($_REQUEST['photo_crop_y2']) ? $_REQUEST['photo_crop_y2'] : $rh;
            $cw = isset($_REQUEST['photo_crop_w']) ? $_REQUEST['photo_crop_w'] : $rw;
            $ch = isset($_REQUEST['photo_crop_h']) ? $_REQUEST['photo_crop_h'] : $rh;
        }



        $x1 = $x1 * $ow / $rw; $y1 = $y1 * $oh / $rh;
        $x2 = $x2 * $ow / $rw; $y2 = $y2 * $oh / $rh;
        $cw = $cw * $ow / $rw; $ch = $ch * $oh / $rh;

        if($cw == 0 || $ch == 0) {
            $cw = $ow;
            $ch = $oh;
        }

         
          $dest = imagecreatetruecolor($newwidth, $newheight);

          // keep transparency with pngs

             imagesavealpha($dest, true);
             imagealphablending($dest, false);
             $transparent = imagecolorallocatealpha($dest, 0, 0, 0, 127);
             imagefill($dest, 0, 0, $transparent);

            imagecopyresampled($dest, $source, 0, 0, $x1, $y1, $newwidth, $newheight, $cw, $ch);
        
             
            imagepng($dest, $filepath);

    
        imagedestroy($source);
        imagedestroy($dest);

        $file = $filepath;
        $l_sNewAbsoluteFilePath = $filepath;

        $l_sNewPath = Utils::pushFileToS3($l_sNewAbsoluteFilePath,$file_a3_path);



        // // Big Thumb No Watermark
        $src = imagecreatefrompng($file);

        list($width,$height)=getimagesize($file);

        $newwidth=114;
        $newheight=($height/$width)*114;
        $tmp=imagecreatetruecolor($newwidth,$newheight);

         imagecopyresampled($tmp,$src,0,0,0,0,$newwidth,$newheight,$width,$height);

         imagepng($tmp,$filepath_big_thumb,100);

         imagedestroy($src);
         imagedestroy($tmp);

        $l_sNewAbsoluteFilePath = $filepath_big_thumb;

        $l_sNewPath = Utils::pushFileToS3($l_sNewAbsoluteFilePath,$file_a3_path_big_thumb);


        // // Thumb No Watermark
        $src = imagecreatefromjpeg($file);

        list($width,$height)=getimagesize($file);

        $newwidth=320;
        $newheight=($height/$width)*320;
        $tmp=imagecreatetruecolor($newwidth,$newheight);

        imagecopyresampled($tmp,$src,0,0,0,0,$newwidth,$newheight,$width,$height);

        imagejpeg($tmp,$filepath_thumb,100);

        imagedestroy($src);
        imagedestroy($tmp);

        $l_sNewAbsoluteFilePath = $filepath_thumb;

        $l_sNewPath = Utils::pushFileToS3($l_sNewAbsoluteFilePath,$file_a3_path_thumb);


        // // Small Thumb No Watermark
        $src = imagecreatefromjpeg($file);

        list($width,$height)=getimagesize($file);

        $newwidth=180;
        $newheight=($height/$width)*180;
        $tmp=imagecreatetruecolor($newwidth,$newheight);

        imagecopyresampled($tmp,$src,0,0,0,0,$newwidth,$newheight,$width,$height);

        imagejpeg($tmp,$filepath_small_thumb,100);

        imagedestroy($src);
        imagedestroy($tmp);

        $l_sNewAbsoluteFilePath = $filepath_small_thumb;

        $l_sNewPath = Utils::pushFileToS3($l_sNewAbsoluteFilePath,$file_a3_path_small_thumb);
        
       

        $filemain = str_replace(UPLOAD_DIR, "", $filepath);

  

        @unlink($filepath);
        @unlink($filepath_thumb);
        @unlink($filepath_big_thumb);
        @unlink($filepath_small_thumb);

        return $filemain;
    }

    ?>