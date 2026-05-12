import os
import re

base_dir = '/Users/kite1412/Projects/Web/Recloth'

# Files to update for user pages
user_files = [
    'index.php',
    'src/user/register.php',
    'src/user/forgot_password.php',
    'src/user/detail_product.php',
    'src/user/profile.php',
    'src/user/payment.php',
    'src/user/catalog.php',
    'src/user/cart.php',
    'src/user/login.php',
    'src/user/category.php'
]

# Files to update for admin pages
admin_files = [
    'src/admin/orders.php',
    'src/admin/customers.php',
    'src/admin/dashboard.php',
    'src/admin/products.php',
    'src/admin/categories.php'
]

user_root_replacement = """        :root {
            --bg: #f3eddf;
            --text: #2e3522;
            --muted: #6b735c;
            --line: #cbd5bb;
            --white: #bac6a9;
            --black: #36442c;
            --success: #1ea672;
            --danger: #d24e4e;
            --radius: 18px;
        }"""

admin_root_replacement = """    :root {
      --sidebar-bg: #bac6a9;
      --sidebar-text: #4e5a42;
      --sidebar-active: #a8b696;
      --main-bg: #f3eddf;
      --card-bg: #bac6a9;
      --border: #a4b391;
      --text-primary: #2e3522;
      --text-secondary: #4e5a42;
      --black: #36442c;
      --blue: #36442c;
      --blue-light: #bac6a9;
      --green: #1ea672;
      --green-light: #e8f6f1;
      --yellow: #ca8a04;
      --yellow-light: #fef9c3;
      --red: #d24e4e;
      --red-light: #fbeeee;
      --gray: #6f6f6f;
      --gray-light: #f1f1f1;
      --shadow: 0 8px 18px rgba(17, 17, 17, 0.04);
      --radius: 16px;
      --radius-sm: 8px;
      --font: 'Montserrat', sans-serif;
      --font-title: 'Archivo Black', sans-serif;
      --mono: 'JetBrains Mono', monospace;
    }"""

def update_user_file(filepath):
    full_path = os.path.join(base_dir, filepath)
    if not os.path.exists(full_path):
        print(f"File not found: {full_path}")
        return
        
    with open(full_path, 'r') as f:
        content = f.read()
        
    # Replace root block
    content = re.sub(r'\s*:root\s*\{[^}]*\}', '\n' + user_root_replacement, content)
    
    # Replace body background if it uses linear gradient
    content = re.sub(r'body\s*\{\s*background:\s*linear-gradient\([^;]+;\s*color:\s*var\(--text\);\s*font-family:\s*"Montserrat",\s*sans-serif;\s*line-height:\s*1\.4;\s*\}', 
                     'body {\n            background: var(--bg);\n            color: var(--text);\n            font-family: "Montserrat", sans-serif;\n            line-height: 1.4;\n        }', content)
                     
    content = re.sub(r'body\s*\{\s*background:\s*#f9f9f9;\s*color:\s*var\(--text\);\s*font-family:\s*"Montserrat",\s*sans-serif;\s*line-height:\s*1\.4;\s*\}', 
                     'body {\n            background: var(--bg);\n            color: var(--text);\n            font-family: "Montserrat", sans-serif;\n            line-height: 1.4;\n        }', content)
                     
    with open(full_path, 'w') as f:
        f.write(content)
    print(f"Updated user file: {filepath}")

def update_admin_file(filepath):
    full_path = os.path.join(base_dir, filepath)
    if not os.path.exists(full_path):
        print(f"File not found: {full_path}")
        return
        
    with open(full_path, 'r') as f:
        content = f.read()
        
    # Replace root block
    content = re.sub(r'\s*:root\s*\{[^}]*\}', '\n' + admin_root_replacement, content)
    
    with open(full_path, 'w') as f:
        f.write(content)
    print(f"Updated admin file: {filepath}")

for f in user_files:
    update_user_file(f)

for f in admin_files:
    update_admin_file(f)
