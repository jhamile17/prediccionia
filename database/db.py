import pymysql
from config import Config

print("HOST:", Config.DB_HOST)
print("PORT:", Config.DB_PORT)
print("USER:", Config.DB_USER)
print("DATABASE:", Config.DB_NAME)

def get_connection():
    return pymysql.connect(
        host=Config.DB_HOST,
        port=Config.DB_PORT,
        user=Config.DB_USER,
        password=Config.DB_PASSWORD,
        database=Config.DB_NAME,
        cursorclass=pymysql.cursors.DictCursor
    )