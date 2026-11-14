with open('D:/MyPerk-New/myperk-backend/debug.log', 'r') as f:
    lines = f.readlines()

# Print the last 100 lines
start = max(0, len(lines) - 100)
for i in range(start, len(lines)):
    print(f'{i}: {lines[i]}', end='')