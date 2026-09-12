<img src="logo.png" align="right" height="64px" />

# Welcome to DockerSAP

### Project overview
DockSAP was created for the purpose of optimizing the traditional paper-based docket process through digitalization.  This system was designed to ensure secure employee authentication using facial recognition and secure badge ID login. Each case uploaded through the system follows a clear lifecycle to ensure fair review and transparency, along with the ability to apply and approve protection orders. DockSAP will provide civil servants with a powerful tool to catcher, record, and retrieve information effectively and securely. This system leverages secure authentication and verification to ensure that each case is accessible to only personal which need to have that access and tracks logs to determine which users accessed certain information.

### Objectives  
* Leverage AI to identify users from camera input.  
* Secure access control through verified user privilege.  
* Encourage easy case tracking and categorization of cases based on key characteristics.  
* Remove and relocate key control of cases to limited individuals (only few people have 
the power to delete and change case statuses) along with grating access to key 
information.  
* Limit information shared with users.  
* Allow for live updates to victims from the comfort of their homes.  
* Easy and secure access to information for relevant offices judges and magistrate **(coming in future update)**.  
User friendly interface.

## Scope and Limitations

1. **Digital case Management**
Replace paper-based case dockets with a secure digital system. Store, update, and track 
case information in real time.
2. **User Authentication and Security** 
Implement facial recognition to verify identities of 
police officers and authorized users. Enable secure login using unique key-based 
access. Perform live background verification using real-time image recognition to 
prevent impersonation.  
3. **Data Protection and Integrity**
Protect sensitive data from unauthorized access, 
loss, or tampering. Ensure traceability and accountability for all actions taken within the 
system. 
4. **User-Friendly Interface**
Design the platform to be simple and easy to use for police 
staff with minimal technical background.  
5. **Access Logs and Monitoring**  
Log every access and modification made to case files. Track when and by whom the 
case data was accessed or edited.

***(Current - LIMITATIONS)***  
1. **Crime Prevention and Investigation**
The system will not investigate or prevent 
crimes it only supports case documentation and management.  
2. **Offline Accessibility**
The system may require internet or network access, which could be a limitation in rural or
under-connected areas. 
3. **Hardware Dependency**
The effectiveness of facial recognition and live verification 
will depend on compatible camera(Quality) hardware and system performance. 
4. **Witness and Victim Protection**
While it helps track and manage witness data, 
the system will not physically protect witnesses from threats or intimidation. 
5. **Legal and Judicial Processes**
DockSAP is focused on police-level 
documentation, not on prosecution, court trials, or sentencing.  
6. **Language and Training Barriers**
Users may need training to effectively use 
biometric and security features, which could delay adoption.

## Main Features

1. **Authentication**
* Facial recognition login
* Manual Badge ID login
* Session-based authentication
* Role-based redirection
* Officer and Commander roles
2. **Case Management**
* Open new cases
* View ongoing cases
* View cold cases
* View closed cases
* View individual case details
* Reopen cold cases
* Delete eligible cold cases
Assign cases to officers
3. **Protection Orders**
* File protection orders
* Link protection orders to cases
* View protection orders
* Track protection-order status
4. **Commander Dashboard**
* View overall case statistics
* View all cases
* View assigned officers
* Assign cases
* Monitor case statuses
5. **Officer Dashboard**
* View case information
* Access case-related functionality
* Manage assigned/available case information
6. **Document Handling(in development)**
*
*
*
*

## Technology Stack

**Frontend**
* Html
* CSS
* JavaScript
* Browser Camera API

**Backend**
* PHP
* Python
* Flask
* Flask-CORS

**Database**
* MySQL / MariaDB
* MySQL Connector/Python
* PHP MySQLi

**Computer Vision**
* OpenCV
* OpenCV Contrib
* Haar Cascade Classifier
* LBPH Face Recognizer

**Development Environment**
* XAMPP
* Apaches
* MariaDB/MySQL
* Python environment
* Web browser

## System Architecture

![Flowchart](https://raw.githubusercontent.com/MkhontoSB/SAPS-D/refs/heads/main/docs/Flows.svg)

## users