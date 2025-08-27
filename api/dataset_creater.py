#import packages

import base64
import os
import cv2
import numpy as np
import mysql.connector  
from pathlib import Path

# MySQL configuration
mysql_config = {
    'host': '127.0.0.1',
    'user': 'root',  
    'password': 'Spear@20',  
    'database': 'saps_db'
}


base_dir = Path("C:\\Projects\\Docker\\api")
dataset_dir = base_dir / "dataset"

# Create directories if they don't exist
dataset_dir.mkdir(parents=True, exist_ok=True)

# Initialize face detector
face_cascade_path = base_dir / "haarcascade_frontalface_default.xml"
faceDetect = cv2.CascadeClassifier(str(face_cascade_path))

def insertorupdate(Badge_ID, Name, Rank):
    """Insert or update a user in the database"""
    try:
        conn = mysql.connector.connect(**mysql_config)
        cursor = conn.cursor()
        cursor.execute("SELECT * FROM employees WHERE Badge_ID=%s", (Badge_ID,))
        isRecordExist = cursor.fetchone() is not None
        
        if isRecordExist:
            cursor.execute("UPDATE employees SET Name=%s, `Rank`=%s WHERE Badge_ID=%s", 
                          (Name, Rank, Badge_ID))
        else:
            cursor.execute("INSERT INTO employees (Badge_ID, Name, `Rank`) VALUES (%s,%s,%s)", 
                          (Badge_ID, Name, Rank))
            
        conn.commit()
        conn.close()
        return True
    except Exception as e:
        print(f"Database error: {e}")
        return False


def capture_faces_from_camera(Badge_ID, sample_count=20):
    """Capture faces from camera and save to dataset"""
    try:
        cam = cv2.VideoCapture(0)
        if not cam.isOpened():
            return {"success": False, "message": "Could not open camera"}
        
        sampleNum = 0
        print(f"Starting face capture for user {Badge_ID}")
        
        while sampleNum < sample_count:
            ret, img = cam.read()
            if not ret:
                continue
                
            gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
            faces = faceDetect.detectMultiScale(gray, 1.3, 5)
            
            for (x, y, w, h) in faces:
                sampleNum += 1
                filename = dataset_dir / f"user.{Badge_ID}.{sampleNum}.jpg"
                success = cv2.imwrite(str(filename), gray[y:y+h, x:x+w])
                
                if success:
                    print(f"Saved {filename}")
                else:
                    print(f"Failed to save {filename}")
                
                cv2.rectangle(img, (x, y), (x+w, y+h), (0, 255, 0), 2)
                cv2.waitKey(100)
            
            cv2.imshow('Face', img)
            if cv2.waitKey(100) & 0xFF == 27:  # ESC key
                break
        
        cam.release()
        cv2.destroyAllWindows()
        
        
        saved_images = list(dataset_dir.glob(f"user.{Badge_ID}.*.jpg"))
        return {
            "success": True, 
            "count": len(saved_images),
            "message": f"Successfully saved {len(saved_images)} images"
        }
        
    except Exception as e:
        return {"success": False, "message": str(e)}

def process_and_save_face(image_data, badge_id):
    """Process a base64 image and save the detected face"""
    try:
        
        image_data = image_data.split(',')[1]  # Remove data:image/jpeg;base64,
        nparr = np.frombuffer(base64.b64decode(image_data), np.uint8)
        img = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
        
        
        gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
        
        # Detect faces
        faces = faceDetect.detectMultiScale(gray, 1.3, 5)
        
        if len(faces) == 0:
            return {'success': False, 'message': 'No face detected'}
        
        
        existing_images = list(dataset_dir.glob(f"user.{badge_id}.*.jpg"))
        sample_num = len(existing_images) + 1
        
        
        x, y, w, h = faces[0]
        filename = dataset_dir / f"user.{badge_id}.{sample_num}.jpg"
        cv2.imwrite(str(filename), gray[y:y+h, x:x+w])
        
        return {
            'success': True, 
            'count': sample_num,
            'message': f'Face captured successfully ({sample_num}/20)'
        }
    
    except Exception as e:
        return {'success': False, 'message': str(e)}


if __name__ == "__main__":
    Badge_ID = input('Enter your badge ID: ')
    Name = input('Enter your name: ')
    Rank = input('Enter your rank: ')
    
    if insertorupdate(Badge_ID, Name, Rank):
        result = capture_faces_from_camera(Badge_ID)
        print(result["message"])
    else:
        print("Failed to update database")