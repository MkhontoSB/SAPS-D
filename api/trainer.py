import os
import cv2
import numpy as np
from PIL import Image
from pathlib import Path

# Use a cross-platform approach for paths
base_dir = Path("C:\\Projects\\Docker\\api")
recognizer_dir = base_dir / "recognizer"
dataset_dir = base_dir / "dataset"

# Create directories if they don't exist
recognizer_dir.mkdir(parents=True, exist_ok=True)

def train_model():
    """Train the face recognition model"""
    try:
        Recognizer = cv2.face.LBPHFaceRecognizer_create()
        
        def get_images_with_id(path):
            images_paths = [os.path.join(path, f) for f in os.listdir(path)]
            faces = []
            Badge_IDs = []
            
            for single_image_path in images_paths:
                try:
                    face_img = Image.open(single_image_path).convert('L')
                    face_np = np.array(face_img, np.uint8)
                    Badge_ID = int(os.path.split(single_image_path)[-1].split(".")[1])
                    
                    faces.append(face_np)
                    Badge_IDs.append(Badge_ID)
                    cv2.imshow("Training", face_np)
                    cv2.waitKey(10)
                except Exception as e:
                    print(f"Error processing image {single_image_path}: {e}")
                    continue
            
            return np.array(Badge_IDs), faces
        
        Badge_IDs, faces = get_images_with_id(str(dataset_dir))
        
        if len(Badge_IDs) == 0:
            return {"success": False, "message": "No training data found"}
        
        Recognizer.train(faces, Badge_IDs)
        Recognizer.save(str(recognizer_dir / "data.yml"))
        cv2.destroyAllWindows()
        
        return {"success": True, "message": f"Model trained successfully with {len(Badge_IDs)} images"}
    
    except Exception as e:
        return {"success": False, "message": str(e)}


if __name__ == "__main__":
    result = train_model()
    print(result["message"])