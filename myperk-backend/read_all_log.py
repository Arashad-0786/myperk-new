with open('D:/MyPerk-New/myperk-backend/debug.log', 'r') as f:
    lines = f.readlines()
# Print all lines, with line numbers
for i in range(len(lines)):
    print(f'{i}: {lines[i]}', end='')