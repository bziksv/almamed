#!/bin/bash
# Apply correct preview images for posts 869 (dush) and 874 (davlenie) on prod
set -euo pipefail
ROOT=/var/www/almamed.su/data/www/almamed.su
cd "$ROOT"
# expects files already rsynced under wa-data/public/blog/img/375x250/
php -r '
$c=include "wa-config/db.php"; $d=$c["default"];
$m=new mysqli($d["host"],$d["user"],$d["password"],$d["database"]);
$m->set_charset("utf8mb4");
$map=[
  869=>"/wa-data/public/blog/img/375x250/04e764f6479bfcc314cd6f26f6ea538b.jpeg",
  874=>"/wa-data/public/blog/img/375x250/675fd8188580d159022bf5d03d6c8277.jpeg",
];
foreach($map as $id=>$img){
  if(!is_file(".".$img)){fwrite(STDERR,"missing $img\n"); exit(1);}
  $st=$m->prepare("UPDATE blog_post SET image=? WHERE id=?");
  $st->bind_param("si",$img,$id); $st->execute();
  echo "ok $id -> $img\n";
}
'
