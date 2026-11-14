with open('D:/MyPerk-New/myperk-backend/debug.log', 'r') as f:
    lines = f.readlines()

# Find DEBUG_AUTH lines
for i, line in enumerate(lines):
    if 'DEBUG_AUTH' in line:
        # Print 2 lines before and after
        start = max(0, i-2)
        end = min(len(lines), i+3)
        for j in range(start, end):
            print(f'{j}: {lines[j]}', end='')
        print('---')