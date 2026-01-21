-- SQL schema for PostgreSQL
CREATE TABLE IF NOT EXISTS users (
  id SERIAL PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(50) DEFAULT 'user',
  phone VARCHAR(50) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS equipment (
  id SERIAL PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  plate VARCHAR(50) DEFAULT NULL,
  status VARCHAR(20) DEFAULT 'available',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS reservations (
  id SERIAL PRIMARY KEY,
  user_id INT NOT NULL,
  equipment_id INT NOT NULL,
  start_dt TIMESTAMP NOT NULL,
  end_dt TIMESTAMP NOT NULL,
  status VARCHAR(20) DEFAULT 'active',
  total_price DECIMAL(10,2) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE
);

CREATE INDEX idx_reservations_user_id ON reservations(user_id);
CREATE INDEX idx_reservations_equipment_id ON reservations(equipment_id);
CREATE INDEX idx_reservations_start_dt ON reservations(start_dt);
CREATE INDEX idx_reservations_end_dt ON reservations(end_dt);
