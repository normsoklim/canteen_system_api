// Simple script to help with authentication for the payment page
// This would typically be set by your authentication system

// Example: Set auth token after successful login
function setAuthToken(token, remember = false) {
    if (remember) {
        localStorage.setItem('token', token);
    } else {
        sessionStorage.setItem('token', token);
    }
}

// Example: Clear auth token on logout
function clearAuthToken() {
    localStorage.removeItem('token');
    sessionStorage.removeItem('token');
}

// Example: Get auth token
function getAuthToken() {
    return localStorage.getItem('token') || sessionStorage.getItem('token');
}

// For testing purposes, you can manually set a token:
// setAuthToken('eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvYXBpL2xvZ2luIiwiaWF0IjoxNzcyMDk3Mzk5LCJleHAiOjE3NzIxMDA5OTksIm5iZiI6MTc3MjA5NzM5OSwianRpIjoidTNpT0ZQNEoyY2xqR08wZCIsInN1YiI6IjQiLCJwcnYiOiIyM2JkNWM4OTQ5ZjYwMGFkYjM5ZTcwMWM0MDA4NzJkYjdhNTk3NmY3In0.DIqJBgXJQoHlPEH8i9G6mfTAVklA_LuGjgCAT-880Cs');