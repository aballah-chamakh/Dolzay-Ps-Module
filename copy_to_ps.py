import os
import shutil

def copy_directory(src, dst):
    # Clear the destination directory if it exists
    if os.path.exists(dst):
        shutil.rmtree(dst)

    # Create the destination directory if it doesn't exist
    os.makedirs(dst, exist_ok=True)

    # Walk through the source directory
    for root, dirs, files in os.walk(src):
        # Skip the .git directory
        if '.git' in dirs :
            dirs.remove('.git')

        if  ".vscode" in dirs :
            dirs.remove('.vscode')
        
        if 'copy_to_ps.py' in files:
            files.remove('copy_to_ps.py')
        
        if 'test.php' in files:
            files.remove('test.php')

        
        # Determine destination path for each directory and file
        dest_dir = os.path.join(dst, os.path.relpath(root, src))
        os.makedirs(dest_dir, exist_ok=True)
        
        for file in files:
            src_file = os.path.join(root, file)
            dest_file = os.path.join(dest_dir, file)
            shutil.copy2(src_file, dest_file)

# Usage
source_directory = 'C:/Users/chama/Bureau/dolzay'
destination_directory = 'C:/xampp/htdocs/prestashop/modules/dolzay'

copy_directory(source_directory, destination_directory)
