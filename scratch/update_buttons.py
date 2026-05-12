import os
import re

base_dir = '/Users/kite1412/Projects/Web/Recloth'

files_to_check = [
    'index.php',
]

for root_dir, dirs, files in os.walk(os.path.join(base_dir, 'src')):
    for file in files:
        if file.endswith('.php'):
            files_to_check.append(os.path.join(root_dir, file))

primary_color = "#6a7f52"
primary_hover = "#526340"

def update_file(filepath):
    if not os.path.exists(filepath):
        return
        
    with open(filepath, 'r') as f:
        content = f.read()

    original = content
    
    # Add --primary and --primary-hover to :root
    if ':root {' in content and '--primary:' not in content:
        content = re.sub(r'(:root\s*\{)', r'\1\n            --primary: ' + primary_color + ';\n            --primary-hover: ' + primary_hover + ';', content)

    # 1. Replace #111 backgrounds for buttons
    content = content.replace('background: #111;', 'background: var(--primary);')
    content = content.replace('border: 1px solid #111;', 'border: 1px solid var(--primary);')
    
    # 2. Replace hover states that use #000 or #222 (if any)
    # Actually wait, let's just replace the exact backgrounds we know about.
    
    # 3. For hover backgrounds that use var(--black), change to var(--primary-hover)
    content = re.sub(r'(\.btn-[a-zA-Z0-9_-]+:hover\s*\{[^}]*)background:\s*var\(--black\);', r'\1background: var(--primary-hover);', content)
    
    # 4. For other var(--black) backgrounds (like .hero-btn, .btn-print, .btn-add)
    content = re.sub(r'background:\s*var\(--black\);', r'background: var(--primary);', content)
    
    # 5. Admin sidebar uses inline styles or specific styles? Admin sidebar active state has var(--sidebar-active), which is fine.
    
    # 6. Tailwind classes
    content = content.replace('bg-black', 'bg-[var(--primary)]')
    content = content.replace('hover:bg-gray-50 text-black border-black', 'hover:bg-gray-50 text-[var(--primary)] border-[var(--primary)]')

    if content != original:
        with open(filepath, 'w') as f:
            f.write(content)
        print(f"Updated: {filepath}")

for f in files_to_check:
    if not f.startswith('/'):
        update_file(os.path.join(base_dir, f))
    else:
        update_file(f)
