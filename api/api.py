
import time
from flask import Flask, request, jsonify, send_from_directory
from flask_cors import CORS
import cv2
import numpy as np
import mysql.connector  
import os
from dotenv import load_dotenv
import base64
import subprocess
import sys
from pathlib import Path
import hashlib
import hmac

load_dotenv()

app = Flask(__name__)
CORS(app)  


mysql_config = {
    'host': os.getenv('DB_HOST'),
    'port': int(os.getenv('DB_PORT', 3306)),
    'user': os.getenv('DB_USER'),
    'password': os.getenv('DB_PASSWORD'),
    'database': os.getenv('DB_NAME')
}


base_dir = Path(__file__).resolve().parent
dataset_dir = base_dir / "dataset"
recognizer_dir = base_dir / "recognizer"
face_cascade_path = base_dir / "haarcascade_frontalface_default.xml"


dataset_dir.mkdir(parents=True, exist_ok=True)
recognizer_dir.mkdir(parents=True, exist_ok=True)

SECRET_KEY = os.getenv('SECRET_KEY')


def init_db():
    try:
        conn = mysql.connector.connect(
            host=mysql_config['host'],
            user=mysql_config['user'],
            password=mysql_config['password']
        )
        cursor = conn.cursor()
        cursor.execute("CREATE DATABASE IF NOT EXISTS police_application")
        cursor.close()
        conn.close()
        
        
        conn = mysql.connector.connect(**mysql_config)
        cursor = conn.cursor()
        
        
        cursor.execute('''
            CREATE TABLE IF NOT EXISTS employees (
                Badge_ID INTEGER PRIMARY KEY,
                Name VARCHAR(45) NOT NULL,
                `Rank` VARCHAR(45) NOT NULL,
                role VARCHAR(20) DEFAULT 'officer'
            )
        ''')
        
        
        cursor.execute("SHOW COLUMNS FROM employees LIKE 'role'")
        result = cursor.fetchone()
        if not result:
            cursor.execute("ALTER TABLE employees ADD COLUMN role VARCHAR(20) DEFAULT 'officer'")
        
        
        cursor.execute('''
            CREATE TABLE IF NOT EXISTS cases (
                case_id INT AUTO_INCREMENT PRIMARY KEY,
                reporter_name VARCHAR(255) NOT NULL,
                reporter_address TEXT NOT NULL,
                suspect_name VARCHAR(255) DEFAULT NULL,
                date_reported DATE NOT NULL,
                crime_type VARCHAR(100) NOT NULL,
                description TEXT NOT NULL,
                status ENUM('Ongoing','Cold','Closed') DEFAULT 'Ongoing',
                last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                assigned_officer INT DEFAULT NULL,
                FOREIGN KEY (assigned_officer) REFERENCES employees(Badge_ID)
            )
        ''')
        
        
        cursor.execute("SHOW COLUMNS FROM cases LIKE 'assigned_officer'")
        result = cursor.fetchone()
        if not result:
            cursor.execute("ALTER TABLE cases ADD COLUMN assigned_officer INT DEFAULT NULL")
            cursor.execute("ALTER TABLE cases ADD FOREIGN KEY (assigned_officer) REFERENCES employees(Badge_ID)")
        
        conn.commit()
        conn.close()
    except Exception as e:
        print(f"Database initialization error: {e}")
        print(f"Database initialization error: {e}")


init_db()


def import_functions():
    try:
        
        sys.path.append(str(base_dir))
        
        
        try:
            from dataset_creater import insertorupdate, process_and_save_face
        except ImportError:
            print("dataset_creater not found, using fallback implementations")
            insertorupdate = None
            process_and_save_face = None
            
        
        try:
            from trainer import train_model
        except ImportError:
            print("trainer not found, using fallback implementation")
            train_model = None
            
        
        try:
            from detect import get_user, recognize_face
        except ImportError:
            print("detect not found, using fallback implementations")
            get_user = None
            recognize_face = None
            
        return insertorupdate, process_and_save_face, train_model, get_user, recognize_face
        
    except Exception as e:
        print(f"Error importing functions: {e}")
        return None, None, None, None, None

# Import the functions
insertorupdate, process_and_save_face, train_model, get_user, recognize_face = import_functions()

# Fallback implementations if imports failed
def insertorupdate(Badge_ID, Name, Rank):
    try:
        conn = mysql.connector.connect(**mysql_config)
        cursor = conn.cursor()
        cursor.execute("SELECT * FROM employees WHERE Badge_ID=%s", (Badge_ID,))
        isRecordExist = cursor.fetchone() is not None
        
        if isRecordExist:
            cursor.execute("UPDATE employees SET Name=%s, `Rank`=%s WHERE Badge_ID=%s", 
                          (Name, Rank, Badge_ID))
        else:
            cursor.execute("INSERT INTO employees (Badge_ID, Name, `Rank`, role) VALUES (%s,%s,%s,'officer')", 
                          (Badge_ID, Name, Rank))
            
        conn.commit()
        conn.close()
        return True
    except Exception as e:
        print(f"Database error: {e}")
        return False

if process_and_save_face is None:
    def process_and_save_face(image_data, badge_id):
        try:
            image_data = image_data.split(',')[1]  # Remove data:image/jpeg;base64,
            nparr = np.frombuffer(base64.b64decode(image_data), np.uint8)
            img = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
            
            gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
            
            faceDetect = cv2.CascadeClassifier(str(face_cascade_path))
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

if train_model is None:
    def train_model():
        try:
            
            result = subprocess.run([sys.executable, 'trainer.py'], capture_output=True, text=True, cwd=base_dir)
            if result.returncode == 0:
                return {"success": True, "message": "Model trained successfully"}
            else:
                return {"success": False, "message": result.stderr}
        except Exception as e:
            return {"success": False, "message": str(e)}

if get_user is None:
    def get_user(Badge_ID):
        try:
            conn = mysql.connector.connect(**mysql_config)
            cursor = conn.cursor()
            cursor.execute("SELECT Badge_ID, Name, Rank, role FROM employees WHERE Badge_ID=%s", (Badge_ID,))
            user = cursor.fetchone()
            conn.close()
            return user
        except Exception as e:
            print(f"Database error: {e}")
            return None

if recognize_face is None:
    def recognize_face(image_data=None):
        try:
            image_data = image_data.split(',')[1]  
            nparr = np.frombuffer(base64.b64decode(image_data), np.uint8)
            img = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
            
            gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
            
            faceDetect = cv2.CascadeClassifier(str(face_cascade_path))
            faces = faceDetect.detectMultiScale(gray, 1.3, 5)
            
            if len(faces) == 0:
                return {"success": False, "message": "No face detected"}
            
            recognizer = cv2.face.LBPHFaceRecognizer_create()
            recognizer_path = recognizer_dir / 'training_data.yml'
            
            if not recognizer_path.exists():
                return {"success": False, "message": "Model not trained yet"}
                
            recognizer.read(str(recognizer_path))
            
            # Predict the face
            x, y, w, h = faces[0]
            badge_id, conf = recognizer.predict(gray[y:y+h, x:x+w])
            
            profile = get_user(str(badge_id))
            
            if profile:
                return {
                    "success": True,
                    "verified": True,
                    "user": {
                        "badge_id": profile[0],
                        "name": profile[1],
                        "rank": profile[2],
                        "role": profile[3]
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

# Token generation and verification functions for PHP integration
def generate_token(badge_id, name, rank, role): 
    """Generate a secure token for PHP authentication"""
    timestamp = str(int(time.time()))
    data = f"{badge_id}:{name}:{rank}:{role}:{timestamp}"  
    signature = hmac.new(SECRET_KEY.encode(), data.encode(), hashlib.sha256).hexdigest()
    token = f"{data}:{signature}"
    return base64.urlsafe_b64encode(token.encode()).decode()

def verify_token(token):
    """Verify a token generated by generate_token"""
    try:
        decoded = base64.urlsafe_b64decode(token.encode()).decode()
        parts = decoded.split(':')
        if len(parts) != 6:  
            return None
            
        badge_id, name, rank, role, timestamp, signature = parts
        data = f"{badge_id}:{name}:{rank}:{role}:{timestamp}"
        expected_signature = hmac.new(SECRET_KEY.encode(), data.encode(), hashlib.sha256).hexdigest()
        
        
        if signature == expected_signature and int(time.time()) - int(timestamp) < 3600:
            return {
                'badge_id': badge_id,
                'name': name,
                'rank': rank,
                'role': role  
            }
        return None
    except:
        return None

# API routes
@app.route('/register', methods=['POST'])
def register_user():
    try:
        data = request.get_json()
        
        
        if not data or 'badge_id' not in data or 'name' not in data or 'rank' not in data:
            return jsonify({'success': False, 'message': 'Missing required fields'}), 400
        
        badge_id = data['badge_id']
        name = data['name']
        rank = data['rank']
        
        
        if not badge_id.isdigit() or len(badge_id) != 5 or badge_id == '00000':
            return jsonify({'success': False, 'message': 'Invalid badge ID. Must be a 5-digit code that is not all zeros.'}), 400
        
        
        existing_user = get_user(badge_id)
        if existing_user:
            return jsonify({'success': False, 'message': 'User with this badge ID already exists'}), 400
        
        
        success = insertorupdate(badge_id, name, rank)
        if not success:
            return jsonify({'success': False, 'message': 'Failed to register user in database'}), 500
        
        return jsonify({
            'success': True, 
            'message': 'User registered successfully',
            'data': {'badge_id': badge_id, 'name': name, 'rank': rank}
        })
    
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)}), 500

@app.route('/capture_face', methods=['POST'])
def capture_face():
    try:
        data = request.get_json()
        
        if not data or 'badge_id' not in data or 'image' not in data:
            return jsonify({'success': False, 'message': 'Missing required fields'}), 400
        
        badge_id = data['badge_id']
        image_data = data['image']
        
        
        result = process_and_save_face(image_data, badge_id)
        
        if result['success']:
            return jsonify(result)
        else:
            return jsonify({'success': False, 'message': result['message']}), 400
    
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)}), 500

@app.route('/train', methods=['GET'])
def train_model_endpoint():
    try:
        # Train the model
        result = train_model()
        
        if result['success']:
            return jsonify({'success': True, 'message': result['message']})
        else:
            return jsonify({'success': False, 'message': result['message']}), 500
    
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)}), 500

@app.route('/verify', methods=['POST'])
def verify_user():
    try:
        data = request.get_json()
        
        if not data or 'image' not in data:
            return jsonify({'success': False, 'message': 'Missing image data'}), 400
        
        image_data = data['image']
        
        # Recognize the face
        result = recognize_face(image_data=image_data)
        
        if result['success']:
            return jsonify(result)
        else:
            return jsonify({'success': False, 'message': result['message']}), 400
    
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)}), 500

@app.route('/manual-login', methods=['POST'])
def manual_login():
    try:
        data = request.get_json()
        
        if not data or 'badge_id' not in data:
            return jsonify({'success': False, 'message': 'Missing badge ID'}), 400
        
        badge_id = data['badge_id']
        
        
        if not badge_id.isdigit() or len(badge_id) != 5 or badge_id == '00000':
            return jsonify({
                'success': False, 
                'verified': False,
                'message': 'Invalid badge ID format. Must be a 5-digit code that is not all zeros.'
            }), 400
        
        
        user = get_user(badge_id)
        
        if user:
            return jsonify({
                'success': True,
                'verified': True,
                'user': {
                    'badge_id': user[0],
                    'name': user[1],
                    'rank': user[2],
                    'role': user[3]
                },
                'message': 'Login successful'
            })
        else:
            return jsonify({
                'success': True,
                'verified': False,
                'message': 'Badge ID not found'
            })
    
    except Exception as e:
        return jsonify({
            'success': False,
            'verified': False,
            'message': f'Error during manual login: {str(e)}'
        }), 500

    
from flask import redirect

@app.route('/generate-token', methods=['POST'])
def generate_token_endpoint():
    try:
        data = request.get_json()
        
        if not data or 'badge_id' not in data or 'name' not in data or 'rank' not in data or 'role' not in data:
            return jsonify({'success': False, 'message': 'Missing required fields'}), 400
        
        
        token = generate_token(data['badge_id'], data['name'], data['rank'], data['role'])
        
        
        return jsonify({
            'success': True, 
            'token': token,
            'redirect_url': f"http://localhost/DockSAP/login.php?token={token}"
        })
    
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)}), 500




@app.route('/php-auth', methods=['POST'])
def php_auth():
    """Endpoint for PHP to verify tokens"""
    try:
        data = request.get_json()
        
        if not data or 'token' not in data:
            return jsonify({'success': False, 'message': 'Missing token'}), 400
        
        token = data['token']
        user_data = verify_token(token)
        
        if user_data:
            return jsonify({
                'success': True,
                'verified': True,
                'user': user_data  
            })
        else:
            return jsonify({
                'success': True,
                'verified': False,
                'message': 'Invalid or expired token'
            })
    
    except Exception as e:
        return jsonify({
            'success': False,
            'verified': False,
            'message': f'Error during token verification: {str(e)}'
        }), 500
    
@app.route('/get-officers', methods=['GET'])
def get_officers():
    """Get list of all officers for assignment"""
    try:
        conn = mysql.connector.connect(**mysql_config)
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT Badge_ID, Name, Rank FROM employees WHERE role = 'officer'")
        officers = cursor.fetchall()
        conn.close()
        
        return jsonify({
            'success': True,
            'officers': officers
        })
    
    except Exception as e:
        return jsonify({
            'success': False,
            'message': f'Error fetching officers: {str(e)}'
        }), 500

@app.route('/assign-case', methods=['POST'])
def assign_case():
    """Assign a case to an officer"""
    try:
        data = request.get_json()
        
        if not data or 'case_id' not in data or 'officer_id' not in data:
            return jsonify({'success': False, 'message': 'Missing required fields'}), 400
        
        case_id = data['case_id']
        officer_id = data['officer_id']
        
        conn = mysql.connector.connect(**mysql_config)
        cursor = conn.cursor()
        cursor.execute("UPDATE cases SET assigned_officer = %s WHERE case_id = %s", (officer_id, case_id))
        conn.commit()
        conn.close()
        
        return jsonify({
            'success': True,
            'message': 'Case assigned successfully'
        })
    
    except Exception as e:
        return jsonify({
            'success': False,
            'message': f'Error assigning case: {str(e)}'
        }), 500

@app.route('/delete-case', methods=['POST'])
def delete_case():
    """Delete a case (only cold cases can be deleted)"""
    try:
        data = request.get_json()
        
        if not data or 'case_id' not in data:
            return jsonify({'success': False, 'message': 'Missing case ID'}), 400
        
        case_id = data['case_id']
        
        conn = mysql.connector.connect(**mysql_config)
        cursor = conn.cursor()
        
        # Check if case is cold
        cursor.execute("SELECT status FROM cases WHERE case_id = %s", (case_id,))
        case_status = cursor.fetchone()
        
        if not case_status:
            conn.close()
            return jsonify({
                'success': False,
                'message': 'Case not found'
            }), 404
        
        if case_status[0] != 'Cold':
            conn.close()
            return jsonify({
                'success': False,
                'message': 'Only cold cases can be deleted'
            }), 400
        
        # Delete the case
        cursor.execute("DELETE FROM cases WHERE case_id = %s", (case_id,))
        conn.commit()
        conn.close()
        
        return jsonify({
            'success': True,
            'message': 'Case deleted successfully'
        })
    
    except Exception as e:
        return jsonify({
            'success': False,
            'message': f'Error deleting case: {str(e)}'
        }), 500

@app.route('/reopen-case', methods=['POST'])
def reopen_case():
    """Reopen a cold case (change status to Ongoing)"""
    try:
        data = request.get_json()
        
        if not data or 'case_id' not in data:
            return jsonify({'success': False, 'message': 'Missing case ID'}), 400
        
        case_id = data['case_id']
        
        conn = mysql.connector.connect(**mysql_config)
        cursor = conn.cursor()
        
        
        cursor.execute("SELECT status FROM cases WHERE case_id = %s", (case_id,))
        case_status = cursor.fetchone()
        
        if not case_status:
            conn.close()
            return jsonify({
                'success': False,
                'message': 'Case not found'
            }), 404
        
        if case_status[0] != 'Cold':
            conn.close()
            return jsonify({
                'success': False,
                'message': 'Only cold cases can be reopened'
            }), 400
        
        
        cursor.execute("UPDATE cases SET status = 'Ongoing' WHERE case_id = %s", (case_id,))
        conn.commit()
        conn.close()
        
        return jsonify({
            'success': True,
            'message': 'Case reopened successfully'
        })
    
    except Exception as e:
        return jsonify({
            'success': False,
            'message': f'Error reopening case: {str(e)}'
        }), 500

@app.route('/realtime', methods=['GET'])
def realtime_recognition():
    try:
        return jsonify({
            'success': True,
            'message': 'Realtime recognition is available through the standalone detect.py script'
        })
    
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)}), 500

# Serve HTML files
@app.route('/')
def serve_registration():
    return send_from_directory(str(base_dir), 'Registration.html')


@app.route('/face_capturer.html')
def serve_face_capturer():
    return send_from_directory(str(base_dir), 'face_capturer.html')


@app.route('/login')
def serve_login():
    return send_from_directory(str(base_dir), 'login.html')

if __name__ == '__main__':
    app.run(debug=True, port=5000)