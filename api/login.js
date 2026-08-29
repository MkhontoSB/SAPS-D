
        
        const facialOption = document.getElementById('facialOption');
        const manualOption = document.getElementById('manualOption');
        const facialLogin = document.getElementById('facialLogin');
        const manualLogin = document.getElementById('manualLogin');
        const video = document.getElementById('video');
        const verifyBtn = document.getElementById('verifyBtn');
        const manualLoginBtn = document.getElementById('manualLoginBtn');
        const status = document.getElementById('status');
        const manualStatus = document.getElementById('manualStatus');
        const userInfo = document.getElementById('userInfo');
        
        // Switch between login methods
        function switchLoginMethod(method) {
            if (method === 'facial') {
                facialOption.classList.add('active');
                manualOption.classList.remove('active');
                facialLogin.classList.add('active');
                manualLogin.classList.remove('active');
            } else {
                facialOption.classList.remove('active');
                manualOption.classList.add('active');
                facialLogin.classList.remove('active');
                manualLogin.classList.add('active');
            }
        }
        
        // Access the camera
        navigator.mediaDevices.getUserMedia({ video: true })
            .then(stream => {
                video.srcObject = stream;
            })
            .catch(error => {
                console.error('Error accessing camera:', error);
                status.textContent = 'Error accessing camera. Please check permissions or use manual login.';
                status.className = 'error';
                verifyBtn.disabled = true;
            });
        
        
        verifyBtn.addEventListener('click', () => {
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            // Convert to base64
            const imageData = canvas.toDataURL('image/jpeg');
            
            // Update status
            status.textContent = 'Verifying identity...';
            status.className = 'info';
            
            // Send to API
            fetch('http://localhost:5000/verify', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    image: imageData
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.verified) {
                        status.textContent = 'Identity verified successfully!';
                        status.className = 'success';
                        
                        // Display user information
                        document.getElementById('userName').textContent = data.user.name;
                        document.getElementById('userBadgeId').textContent = data.user.badge_id;
                        document.getElementById('userRank').textContent = data.user.rank;
                        userInfo.style.display = 'block';
                        
                        // Generate token for PHP authentication
                        fetch('http://localhost:5000/generate-token', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                badge_id: data.user.badge_id,
                                name: data.user.name,
                                rank: data.user.rank,
                                role: data.user.role || 'officer'
                            })
                        })
                        .then(response => response.json())
                        .then(tokenData => {
                            if (tokenData.success) {
                                // Redirect to PHP login with token
                                window.location.href = tokenData.redirect_url;
                            } else {
                                status.textContent = 'Error generating token: ' + (tokenData.message || 'Unknown error');
                                status.className = 'error';
                            }
                        })
                        .catch(error => {
                            console.error('Token generation error:', error);
                            status.textContent = 'Error generating login token';
                            status.className = 'error';
                        });
                    } else {
                        status.textContent = data.message || 'User not recognized';
                        status.className = 'error';
                    }
                } else {
                    status.textContent = 'Error: ' + data.message;
                    status.className = 'error';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                status.textContent = 'Error verifying identity';
                status.className = 'error';
                userInfo.style.display = 'none';
            });
        });
        
        
        manualLoginBtn.addEventListener('click', () => {
            const badgeId = document.getElementById('manualBadgeId').value;
            
            if (!badgeId) {
                manualStatus.textContent = 'Please enter your badge ID';
                manualStatus.className = 'error';
                return;
            }
            
            // Validate badge ID format (5 digits, not all zeros)
            if (!/^\d{5}$/.test(badgeId) || badgeId === '00000') {
                manualStatus.textContent = 'Please enter a valid 5-digit badge ID';
                manualStatus.className = 'error';
                return;
            }
            
            manualStatus.textContent = 'Verifying badge ID...';
            manualStatus.className = 'info';
            
            
            fetch('http://localhost:5000/manual-login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    badge_id: badgeId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.verified) {
                        manualStatus.textContent = 'Login successful!';
                        manualStatus.className = 'success';
                        
                        // Display user information
                        document.getElementById('userName').textContent = data.user.name;
                        document.getElementById('userBadgeId').textContent = data.user.badge_id;
                        document.getElementById('userRank').textContent = data.user.rank;
                        userInfo.style.display = 'block';
                        
                        // Generate token for PHP authentication
                        fetch('http://localhost:5000/generate-token', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                badge_id: data.user.badge_id,
                                name: data.user.name,
                                rank: data.user.rank,
                                role: data.user.role || 'officer'
                            })
                        })
                        .then(response => response.json())
                        .then(tokenData => {
                            if (tokenData.success) {
                                // Redirect to PHP login with token
                                window.location.href = tokenData.redirect_url;
                            } else {
                                manualStatus.textContent = 'Error generating token: ' + (tokenData.message || 'Unknown error');
                                manualStatus.className = 'error';
                            }
                        })
                        .catch(error => {
                            console.error('Token generation error:', error);
                            manualStatus.textContent = 'Error generating login token';
                            manualStatus.className = 'error';
                        });
                    } else {
                        manualStatus.textContent = data.message || 'Badge ID not recognized';
                        manualStatus.className = 'error';
                    }
                } else {
                    manualStatus.textContent = 'Error: ' + data.message;
                    manualStatus.className = 'error';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                manualStatus.textContent = 'Error during login';
                manualStatus.className = 'error';
            });
        });