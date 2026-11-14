#!/usr/bin/env python3
import pymysql
conn = pymysql.connect(host='127.0.0.1', port=3307, user='root', dbname='perk_db')
cur = conn.cursor()
# Check existing requests
cur.execute('SELECT * FROM loyalty_stamp_requests')
rows = cur.fetchall()
print(f'Existing stamp requests: {len(rows)}')
for r in rows:
    print(r)
# Clear old requests
cur.execute("DELETE FROM loyalty_stamp_requests WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)")
conn.commit()
cur.execute('SELECT * FROM loyalty_stamp_requests')
rows = cur.fetchall()
print(f'After cleanup - Existing stamp requests: {len(rows)}')
for r in rows:
    print(r)
conn.close()