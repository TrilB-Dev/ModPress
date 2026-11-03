from pathlib import Path
p = Path(r'c:\Users\Anthony\OneDrive\Modding\Wordpress Plugins\ModPress\src\Admin\Manager\Mods\ModGroups.php')
data = p.read_bytes()
print(repr(data[:200]))
