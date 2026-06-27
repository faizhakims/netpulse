import re

with open('database/mal_monitoring_db.sql', 'r', encoding='utf-8') as f:
    content = f.read()

# Extract table blocks
blocks = re.findall(r'CREATE TABLE (.*?) \((.*?)\) ENGINE=', content, re.DOTALL)
for table_name, columns_str in blocks:
    print(f"Table: {table_name}")
    columns = [col.strip() for col in columns_str.split('\n') if col.strip() and not col.strip().startswith('--')]
    for col in columns:
        print(f"  {col}")
    print()
