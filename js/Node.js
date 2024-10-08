require('dotenv').config();
const sql = require('mssql');

const config = {
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
  server: process.env.DB_SERVER,
  database: process.env.DB_DATABASE,
  port: parseInt(process.env.DB_PORT),
};

sql.connect(config, (err) => {
  if (err) {
    console.error('Connection error:', err);
    return;
  }
  console.log('Connected to SQL Server');
});
