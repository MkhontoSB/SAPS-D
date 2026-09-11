import base64
import cv2
import numpy as np
import mysql.connector 
from pathlib import Path
import os
from dotenv import load_dotenv

load_dotenv()


# MySQL configuration
mysql_config = {
    'host': os.getenv('DB_HOST'),
    'port': int(os.getenv('DB_PORT', 3306)),
    'user': os.getenv('DB_USER'),
    'password': os.getenv('DB_PASSWORD'),
    'database': os.getenv('DB_NAME')
}


base_dir = Path(__file__).resolve().parent
recognizer_dir = base_dir / "recognizer"
face_cascade_path = base_dir / "haarcascade_frontalface_default.xml"

# Initialize face detector and recognizer
faceDetect = cv2.CascadeClassifier(str(face_cascade_path))

def get_user(Badge_ID):
    """Get user profile from database"""
    try:


        conn = mysql.connector.connect(**mysql_config)
        cursor = conn.cursor()
        cursor.execute("SELECT * FROM employees WHERE Badge_ID=%s", (Badge_ID,))
        profile = cursor.fetchone()
        conn.close()
        return profile
    except Exception as e:
        print(f"Database error: {e}")
        return None



def recognize_face(image_path=None, image_data=None):
    """Recognize a face from an image file or image data"""
    try:


        # Load image from file or data
        if image_path:
            img = cv2.imread(str(image_path))
        elif image_data:
            
            image_data = image_data.split(',')[1]  
            nparr = np.frombuffer(base64.b64decode(image_data), np.uint8)
            img = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
        else:
            return {"success": False, "message": "No image provided"}
        
        
        gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
        
        # Detect faces
        faces = faceDetect.detectMultiScale(gray, 1.3, 5)
        
        if len(faces) == 0:
            return {"success": False, "message": "No face detected"}
        
        
        recognizer = cv2.face.LBPHFaceRecognizer_create()
        recognizer_path = recognizer_dir / "training_data.yml"
        
        if not recognizer_path.exists():
            return {"success": False, "message": "Model not trained yet"}
            
        recognizer.read(str(recognizer_path))
        
        
        x, y, w, h = faces[0]
        Badge_ID, conf = recognizer.predict(gray[y:y+h, x:x+w])
        
        
        profile = get_user(str(Badge_ID))
        
        if profile:
            return {
                "success": True,
                "verified": True,
                "user": {
                    "badge_id": profile[0],
                    "name": profile[1],
                    "rank": profile[2],
                    "role": profile[3] if len(profile) > 3 else 'officer'
                },
                "confidence": float(conf)
            }
        else:
            return {
                "success": True,
                "verified": False,
                "message": "User not recognized"
            }
    
    except Exception as e:
        return {"success": False, "message": str(e)}

def realtime_recognition():
    """Run real-time face recognition from webcam"""
    try:


        recognizer = cv2.face.LBPHFaceRecognizer_create()
        recognizer_path = recognizer_dir / "training_data.yml"
        
        if not recognizer_path.exists():
            print("Model not trained yet")
            return
        
        recognizer.read(str(recognizer_path))
        cam = cv2.VideoCapture(0)
        
        if not cam.isOpened():
            print("Could not open camera")
            return
        
        while True:
            ret, img = cam.read()
            if not ret:
                continue
                
            gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
            faces = faceDetect.detectMultiScale(gray, 1.3, 5)
            
            for (x, y, w, h) in faces:
                cv2.rectangle(img, (x, y), (x+w, y+h), (0, 255, 0), 2)
                Badge_ID, conf = recognizer.predict(gray[y:y+h, x:x+w])
                profile = get_user(Badge_ID)
                
                if profile:
                    cv2.putText(img, "Name: " + str(profile[1]), (x, y + h + 20), 
                                cv2.FONT_HERSHEY_COMPLEX, 1, (0, 255, 127), 2)
                    cv2.putText(img, "Rank: " + str(profile[2]), (x, y + h + 45), 
                                cv2.FONT_HERSHEY_COMPLEX, 1, (0, 255, 127), 2)
                else:
                    cv2.putText(img, "Unknown", (x, y + h + 20), 
                                cv2.FONT_HERSHEY_COMPLEX, 1, (0, 0, 255), 2)
            
            cv2.imshow("Face Recognition", img)
            if cv2.waitKey(1) == ord('q'):
                break
        
        cam.release()
        cv2.destroyAllWindows()
        
    except Exception as e:
        print(f"Error in realtime recognition: {e}")


if __name__ == "__main__":
    realtime_recognition()