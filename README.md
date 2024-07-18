# Plugins V2

Connect to mysql:
```sh
sudo mysql
```

Then create database and user:
```sql
CREATE DATABASE plugins;
CREATE USER 'plugins'@'localhost' IDENTIFIED BY 'snigulp';
GRANT ALL ON plugins.* TO 'plugins'@'localhost';
```

And import database:
```sh
sudo mysql plugins < sql/plugins.sql
```
