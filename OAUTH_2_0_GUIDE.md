# OAuth 2.0 Implementation Guide

## Overview

This implementation provides a complete OAuth 2.0 server with support for all major grant types:

- **Authorization Code Flow** - For web applications
- **Password Grant** - For trusted first-party applications
- **Client Credentials Grant** - For server-to-server communication
- **Refresh Token Grant** - For token renewal

## Database Structure

### Tables Created
- `oauth_clients` - OAuth client applications
- `oauth_auth_codes` - Authorization codes for code flow
- `oauth_access_tokens` - Access tokens
- `oauth_refresh_tokens` - Refresh tokens

### Models
- `OAuthClient` - Client management
- `OAuthAuthCode` - Authorization code handling
- `OAuthAccessToken` - Access token management
- `OAuthRefreshToken` - Refresh token handling

## API Endpoints

### OAuth 2.0 Endpoints

#### 1. Authorization Endpoint
```
POST /api/oauth/authorize
```
**Purpose:** Generate authorization code for authorization code flow

**Request Body:**
```json
{
  "response_type": "code",
  "client_id": "7fb659e5-0892-4140-88f7-46dfcbeb9165",
  "redirect_uri": "http://localhost:3000/callback",
  "scope": "read write",
  "state": "random_state_string",
  "email": "user@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "authorization_code": "abc123def456",
  "expires_in": 600,
  "redirect_uri": "http://localhost:3000/callback"
}
```

#### 2. Token Endpoint
```
POST /api/oauth/token
```
**Purpose:** Exchange authorization code for tokens or get tokens directly

**Authorization Code Grant:**
```json
{
  "grant_type": "authorization_code",
  "client_id": "7fb659e5-0892-4140-88f7-46dfcbeb9165",
  "client_secret": "JKBETgUmpevbjGfL5ZC3BjnU7WT5wfy5H0VhTnrk",
  "code": "abc123def456",
  "redirect_uri": "http://localhost:3000/callback"
}
```

**Password Grant:**
```json
{
  "grant_type": "password",
  "client_id": "7fb659e5-0892-4140-88f7-46dfcbeb9165",
  "client_secret": "JKBETgUmpevbjGfL5ZC3BjnU7WT5wfy5H0VhTnrk",
  "username": "user@example.com",
  "password": "password123",
  "scope": "read write"
}
```

**Client Credentials Grant:**
```json
{
  "grant_type": "client_credentials",
  "client_id": "9e05dea8-08db-4111-bfea-8120998690a2",
  "client_secret": "BVrGjXpX50XN30PPETuZhHEsrcClrs9Vf94sPTrf",
  "scope": "read"
}
```

**Refresh Token Grant:**
```json
{
  "grant_type": "refresh_token",
  "client_id": "7fb659e5-0892-4140-88f7-46dfcbeb9165",
  "client_secret": "JKBETgUmpevbjGfL5ZC3BjnU7WT5wfy5H0VhTnrk",
  "refresh_token": "refresh_token_here"
}
```

**Token Response:**
```json
{
  "access_token": "access_token_here",
  "token_type": "Bearer",
  "expires_in": 3600,
  "refresh_token": "refresh_token_here",
  "scope": "read write"
}
```

#### 3. Token Info Endpoint
```
GET /api/oauth/token-info
```
**Headers:** `Authorization: Bearer {access_token}`

**Response:**
```json
{
  "active": true,
  "client_id": "7fb659e5-0892-4140-88f7-46dfcbeb9165",
  "user_id": 1,
  "scope": "read write",
  "exp": 1732272000
}
```

#### 4. Token Revocation Endpoint
```
POST /api/oauth/revoke
```
**Request Body:**
```json
{
  "token": "access_token_here",
  "client_id": "7fb659e5-0892-4140-88f7-46dfcbeb9165",
  "client_secret": "JKBETgUmpevbjGfL5ZC3BjnU7WT5wfy5H0VhTnrk"
}
```

#### 5. Scopes Endpoint
```
GET /api/oauth/scopes
```
**Response:**
```json
{
  "scopes": {
    "read": "Read access to resources",
    "write": "Write access to resources",
    "delete": "Delete access to resources",
    "admin": "Administrative access"
  }
}
```

#### 6. Client Creation Endpoint
```
POST /api/oauth/clients
```
**Request Body:**
```json
{
  "name": "My Application",
  "secret": "optional_secret_here",
  "redirect": "http://localhost:3000/callback",
  "personal_access_client": false,
  "password_client": true,
  "user_id": 1
}
```

## Available OAuth Clients

### 1. InHouse Password Client
- **ID:** `7fb659e5-0892-4140-88f7-46dfcbeb9165`
- **Secret:** `JKBETgUmpevbjGfL5ZC3BjnU7WT5wfy5H0VhTnrk`
- **Type:** Confidential
- **Grants:** Password, Authorization Code, Refresh Token

### 2. InHouse Client Credentials Client
- **ID:** `9e05dea8-08db-4111-bfea-8120998690a2`
- **Secret:** `BVrGjXpX50XN30PPETuZhHEsrcClrs9Vf94sPTrf`
- **Type:** Confidential
- **Grants:** Client Credentials

### 3. InHouse Public Client
- **ID:** `e638223c-0dc8-440f-af97-6510f9277cc1`
- **Secret:** None (Public)
- **Type:** Public
- **Grants:** Authorization Code

## Postman Examples

### 1. Password Grant Flow
```
POST {{BASE_URL}}/api/oauth/token
Content-Type: application/json

{
  "grant_type": "password",
  "client_id": "7fb659e5-0892-4140-88f7-46dfcbeb9165",
  "client_secret": "JKBETgUmpevbjGfL5ZC3BjnU7WT5wfy5H0VhTnrk",
  "username": "user@example.com",
  "password": "password123",
  "scope": "read write"
}
```

### 2. Using Access Token
```
GET {{BASE_URL}}/api/inventories
Authorization: Bearer {{ACCESS_TOKEN}}
Accept: application/json
```

### 3. Refresh Token
```
POST {{BASE_URL}}/api/oauth/token
Content-Type: application/json

{
  "grant_type": "refresh_token",
  "client_id": "7fb659e5-0892-4140-88f7-46dfcbeb9165",
  "client_secret": "JKBETgUmpevbjGfL5ZC3BjnU7WT5wfy5H0VhTnrk",
  "refresh_token": "{{REFRESH_TOKEN}}"
}
```

## Postman Test Scripts

### For Token Endpoint
```javascript
const response = pm.response.json();
if (response.access_token) {
    pm.environment.set("ACCESS_TOKEN", response.access_token);
}
if (response.refresh_token) {
    pm.environment.set("REFRESH_TOKEN", response.refresh_token);
}
console.log("Tokens saved successfully");
```

### For Token Info Endpoint
```javascript
const response = pm.response.json();
if (response.active) {
    console.log("Token is valid");
    console.log("User ID:", response.user_id);
    console.log("Scopes:", response.scope);
} else {
    console.log("Token is invalid");
}
```

## Security Features

1. **Token Expiry:** Access tokens expire in 1 hour
2. **Refresh Token Rotation:** Each refresh generates new tokens
3. **Scope-based Authorization:** Fine-grained access control
4. **Client Authentication:** Confidential clients require secrets
5. **Token Revocation:** Secure token invalidation
6. **Database Storage:** All tokens stored securely

## Maintenance Commands

### List OAuth Clients
```bash
php artisan oauth:list-clients
```

### Cleanup Expired Tokens
```bash
php artisan tokens:cleanup --days=7
```

## Error Responses

### Invalid Client
```json
{
  "error": "invalid_client",
  "error_description": "Client not found or revoked"
}
```

### Invalid Grant
```json
{
  "error": "invalid_grant",
  "error_description": "Invalid credentials"
}
```

### Invalid Token
```json
{
  "error": "invalid_token",
  "error_description": "Token is invalid or expired"
}
```

## Best Practices

1. **Always use HTTPS** in production
2. **Store client secrets securely**
3. **Implement proper error handling**
4. **Use appropriate grant types** for your use case
5. **Regularly rotate refresh tokens**
6. **Monitor token usage** and revoke suspicious tokens
7. **Implement rate limiting** on token endpoints

## Migration from Legacy Auth

The legacy authentication endpoints (`/api/login`, `/api/register`) are still available for backward compatibility, but new applications should use OAuth 2.0 for better security and flexibility.
