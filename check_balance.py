import sys

def check_balance(file_path):
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    stack = []
    pairs = {')': '(', '}': '{', ']': '['}
    last_matches = []
    
    lines = content.split('\n')
    
    for i, line in enumerate(lines):
        for j, char in enumerate(line):
            if char in '({[':
                stack.append((char, i + 1, j + 1))
            elif char in ')}]':
                if not stack:
                    print(f"Extra '{char}' at line {i+1}, column {j+1}")
                    return
                top_char, top_line, top_col = stack.pop()
                if i + 1 == 3424:
                     print(f"TRACE_3424: Line 3424 index {j+1} matched '{top_char}' from {top_line}:{top_col}")
                if i + 1 == 4997:
                     print(f"TRACE_4997: Line 4997 index {j+1} matched '{top_char}' from {top_line}:{top_col}")
                
                if top_char != pairs[char]:
                    print(f"MISMATCH: '{char}' at line {i+1}, column {j+1} tries to close '{top_char}' from line {top_line}, column {top_col}")
                    return
    
    if stack:
        print("Unmatched brackets remaining on stack:")
        for char, line, col in stack:
            print(f"  '{char}' starting at line {line}, column {col}")
        print("Last 20 successful matches:")
        for m in last_matches:
            print(f"  {m}")
    else:
        print("All brackets balanced")

if __name__ == "__main__":
    check_balance(sys.argv[1])
