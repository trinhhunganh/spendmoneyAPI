CREATE DATABASE spendmoneydb_test;
USE spendmoneydb_test;

CREATE TABLE account(
	id int auto_increment primary key,
    accountName varchar(40),
    accountNumber varchar(16),
    currentBalance double,
    email varchar(120)
);

CREATE DATABASE spendmoneydb;
USE spendmoneydb;

CREATE TABLE account(
	id int auto_increment primary key,
    accountName varchar(40),
    accountNumber varchar(16),
    currentBalance double,
    email varchar(120)
);