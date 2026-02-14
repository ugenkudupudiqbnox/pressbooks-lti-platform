-- Initialize databases for LTI Platform
-- This script runs automatically when MySQL container starts for the first time

-- Create Pressbooks database
CREATE DATABASE IF NOT EXISTS pressbooks CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Grant privileges (root already has access, but being explicit)
GRANT ALL PRIVILEGES ON pressbooks.* TO 'root'@'%';
FLUSH PRIVILEGES;

-- Log initialization
SELECT 'Pressbooks database initialized' AS status;
