from pathlib import Path
import re

path = Path(r'c:\Users\Anthony\OneDrive\Modding\Wordpress Plugins\ModPress\src\Admin\Manager\Mods\Mods.php')
text = path.read_text(encoding='utf-8')
pattern = re.compile(
    r"('button_text' => __\( 'Choose Gallery Images', 'modpress' \),\n\s*'show_remove' => true,\n\s*'media_type' => 'image',\n)(?:\s*'multiple' => true,\n\s*'return_value' => 'id',\n)+",
    re.MULTILINE,
)
replacement = """'button_text' => __( 'Choose Gallery Images', 'modpress' ),
\t\t\t\t\t\t\t\t\t\t\t'show_remove' => true,
\t\t\t\t\t\t\t\t\t\t\t'media_type' => 'image',
\t\t\t\t\t\t\t\t\t\t\t'multiple' => true,
\t\t\t\t\t\t\t\t\t\t\t'return_value' => 'id',
"""
text, count = pattern.subn(replacement, text, count=1)
if count != 1:
    raise SystemExit(f'pattern not found: {count}')
path.write_text(text, encoding='utf-8')
print('cleaned gallery config')
