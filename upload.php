<? php

        if($_SERVER ["REQUEST_METHOD"] !== "POST") {
            die("Invalid Request");  
        

        if(!isset($_FILES["pdf"])) {
            die("No File uploaded");
        }

            $_FILE = $_FILES["pdf"];
        if($_FILE["Error"] !== UPLOAD_ERR_OK) {
            die("there was an error while uploading the file");
        }

            /* check file size */
            $maxsize = 10*1024*1024;
                if($_FILE["size"] > $maxsize){
                die("File size too large please reduce the file. Maximum allowed size is 10MB");

            }

            /* check file */
            $extension = strtolower(pathinfo($_FILE["name"], PATHINFO_EXTENSION));
                if ($extension !== "PDF") {
                    die("Only PDF files are allowed");

            }

            /* check Mime */
            $Finfo = new Finfo(FILEINFO_MIME_TYPE);
                $mimeType = $Finfo ->file($_FILE["tmp_name"]);
                    if ($mimeType !== "Application/pdf") {
                        die("The uploaded file type is not a valid pdf.");
        
                    }

            /* creating new directory */
            $uploadDirectory = "uploads/pdf/";
                if (!is_dir($uploadDirectory)) {
                    mkdir($uploadDirectory, 0755, true);
                    
                }

                /* new fileName */
            $newFileName = uniqid("pdf_",true). "pdf";
            $destionation = $uploadDirectory.$newFileName;

            if (!move_uploaded_file($_FILE["tmp_name"], $destionation)) {
                die("Failed to save the file, Please retry");

            }
            echo("PDF successfully uploaded");
            echo("Saved as".htmlspecialchars($newFileName));

        }
            