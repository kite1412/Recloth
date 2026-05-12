import os
import re

base_dir = '/Users/kite1412/Projects/Web/Recloth'

files_to_check = [
    'index.php',
]

for root_dir, dirs, files in os.walk(os.path.join(base_dir, 'src', 'user')):
    for file in files:
        if file.endswith('.php'):
            files_to_check.append(os.path.join(root_dir, file))

def update_file(filepath):
    if not os.path.exists(filepath):
        return
        
    with open(filepath, 'r') as f:
        content = f.read()

    original = content
    
    # 1. Update .cart-icon CSS
    # Old: background: #fff;
    # Old: color: #111;
    # Replace the whole block or just the properties?
    # Let's replace the properties if they exist.
    
    content = re.sub(
        r'(\.cart-icon\s*\{[^\}]*)color:\s*#[0-9a-fA-F]+;', 
        r'\1color: #fff;', 
        content
    )
    content = re.sub(
        r'(\.cart-icon\s*\{[^\}]*)background:\s*#[0-9a-fA-F]+;', 
        r'\1background: var(--primary);', 
        content
    )
    
    # 2. Update logout button inline style
    # Old: style="color: #d24e4e;"
    # New: style="color: #d24e4e; background: var(--bg); border-color: var(--line);"
    content = content.replace('style="color: #d24e4e;"', 'style="color: #d24e4e; background: var(--bg); border-color: var(--line);"')

    if content != original:
        with open(filepath, 'w') as f:
            f.write(content)
        print(f"Updated: {filepath}")

for f in files_to_check:
    if not f.startswith('/'):
        update_file(os.path.join(base_dir, f))
    else:
        update_file(f)
