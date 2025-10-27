# INHouse Backend

A Laravel-based backend API for the INHouse inventory management system with OAuth 2.0 authentication.

## Features

- **User Authentication**: Registration, login, and token-based authentication
- **OAuth 2.0**: Full OAuth 2.0 implementation with clients, authorization codes, and refresh tokens
- **Inventory Management**: 
  - Comprehensive CRUD operations
  - Bulk update capabilities
  - Low stock tracking
  - Quantity adjustment
- **API Endpoints**: RESTful API with JSON responses
- **Database**: MySQL/PostgreSQL support with migrations and seeders

## Requirements

- PHP >= 8.1
- Composer
- MySQL/PostgreSQL
- Node.js & NPM (for frontend assets)

## Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd inhouse-back
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure database**
   - Update `.env` file with your database credentials
   - Run migrations: `php artisan migrate`
   - Seed the database: `php artisan db:seed`

6. **Start the development server**
   ```bash
   php artisan serve
   ```

## API Endpoints

### Authentication
- `POST /api/auth/register` - User registration
- `POST /api/auth/login` - User login
- `POST /api/auth/refresh` - Refresh access token

### OAuth 2.0
- `POST /api/oauth/authorize` - Authorization endpoint
- `POST /api/oauth/token` - Token endpoint
- `POST /api/oauth/refresh` - Refresh token endpoint

### Inventory Management
#### Standard CRUD Operations
- `GET /api/inventory` - List all inventory items
- `POST /api/inventory` - Create new inventory item
- `GET /api/inventory/{id}` - Get specific inventory item
- `PUT /api/inventory/{id}` - Update entire inventory item
- `PATCH /api/inventory/{id}` - Partially update inventory item
- `DELETE /api/inventory/{id}` - Delete inventory item

#### Advanced Inventory Features
- `POST /api/inventory/{id}/adjust` - Adjust item quantity
- `GET /api/inventory/low-stock` - Retrieve low stock items
- `POST /api/inventory/bulk-update` - Update multiple inventory items

## Detailed Documentation

For comprehensive API documentation, please refer to:
- [Inventory API Documentation](INVENTORY_API.md)
- [OAuth 2.0 Guide](OAUTH_2_0_GUIDE.md)

## OAuth 2.0 Setup

The system includes a complete OAuth 2.0 implementation:

1. **Create OAuth Client**
   ```bash
   php artisan oauth:create-client
   ```

2. **List OAuth Clients**
   ```bash
   php artisan oauth:list-clients
   ```

## Testing

Run the test suite:
```bash
php artisan test
```

### Test Coverage
- Unit Tests: Individual component logic
- Feature Tests: API endpoint behaviors
- Authentication Tests
- Inventory Management Tests

## Database Migrations

The project includes comprehensive database migrations for:
- Users table
- OAuth tables (clients, auth codes, access tokens, refresh tokens)
- Inventory table
- Personal access tokens

## Performance Considerations

- Efficient database queries
- Caching mechanisms
- Rate limiting
- Pagination support

## Security Features

- Token-based authentication
- Role-based access control
- Input validation
- Protection against common web vulnerabilities

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

### Contribution Guidelines
- Follow Laravel coding standards
- Write comprehensive tests
- Update documentation
- Ensure code quality

## License

[Add your license here]

## Support

For support and questions, please contact [your contact information].

### Troubleshooting
- Check Laravel log files
- Verify database connections
- Ensure all dependencies are installed
- Review error messages in `storage/logs/laravel.log`
