from pathlib import Path
p = Path(r'c:\Users\Anthony\OneDrive\Modding\Wordpress Plugins\ModPress\src\Admin\Manager\Mods\ModGroups.php')
text = p.read_text(encoding='utf-8')
start = "<?php $this->render_form_row(\n\t\t\t\t\t\t\t\t\t\t\t'entry_parent',\n\t\t\t\t\t\t\t\t\t\t\t__( 'Parent', 'modpress' ),"
if start not in text:
    raise SystemExit('start marker not found')
text = text.replace(start, "<?php if ( $selected_group_hierarchical ) : ?>\n\t\t\t\t\t\t\t\t\t\t\t<?php $this->render_form_row(\n\t\t\t\t\t\t\t\t\t\t\t'entry_parent',\n\t\t\t\t\t\t\t\t\t\t\t__( 'Parent', 'modpress' ),", 1)
close = "\n\t\t\t\t\t\t\t\t\t\t); ?>"
if close not in text:
    raise SystemExit('close marker not found')
text = text.replace(close, "\n\t\t\t\t\t\t\t\t\t\t); ?>\n\t\t\t\t\t\t\t\t\t\t<?php endif; ?>", 1)
p.write_text(text, encoding='utf-8')
print('patched')
