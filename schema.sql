CREATE TABLE contractor
(
  id            INT AUTO_INCREMENT
    PRIMARY KEY,
  username      VARCHAR(255)   NOT NULL,
  password_hash VARCHAR(255)   NOT NULL,
  balance       DECIMAL(10, 2) NOT NULL,
  CONSTRAINT contractor_username_uindex
  UNIQUE (username)
);

CREATE TABLE customer
(
  id            INT AUTO_INCREMENT
    PRIMARY KEY,
  username      VARCHAR(255) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  CONSTRAINT customer_login_uindex
  UNIQUE (username)
);

CREATE TABLE `order`
(
  id              INT AUTO_INCREMENT
    PRIMARY KEY,
  name            VARCHAR(255)   NOT NULL,
  description     TEXT           NOT NULL,
  creator_user_id INT            NOT NULL,
  done            TINYINT(1)     NOT NULL,
  price           DECIMAL(10, 2) NOT NULL
);

CREATE TABLE transaction
(
  id       INT AUTO_INCREMENT
    PRIMARY KEY,
  order_id INT            NOT NULL,
  sum      DECIMAL(10, 2) NOT NULL,
  finished TINYINT(1)     NOT NULL,
  CONSTRAINT transaction_order_id_uindex
  UNIQUE (order_id)
);

CREATE TABLE transaction_user_balance
(
  user_id INT            NOT NULL
    PRIMARY KEY,
  balance DECIMAL(10, 2) NOT NULL
);

CREATE TABLE user
(
  id                INT AUTO_INCREMENT
    PRIMARY KEY,
  username          VARCHAR(255)                  NOT NULL,
  password_hash     VARCHAR(255)                  NOT NULL,
  balance           DECIMAL(10, 2) DEFAULT '0.00' NOT NULL,
  can_create_order  TINYINT(1) DEFAULT '0'        NOT NULL,
  can_execute_order TINYINT(1) DEFAULT '0'        NOT NULL,
  CONSTRAINT user_username_uindex
  UNIQUE (username)
);

