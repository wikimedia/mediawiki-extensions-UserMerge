<?php 
#coding: utf-8
/** \file
* \brief Internationalization file for the User Merge and Delete Extension.
*/

$messages = array();

$messages['en'] = array(
	'usermerge'                     => 'User merge and delete',
	'usermerge-desc'                => "[[Special:UserMerge|Merges references from one user to another user]] in the wiki database - will also delete old users following merge. Requires ''usermerge'' privileges",
	'usermerge-badolduser' 		=> 'Invalid old username',
	'usermerge-badnewuser' 		=> 'Invalid new username',
	'usermerge-nonewuser' 		=> 'Empty new username - assuming merge to $1.<br />Click <u>Merge User</u> to accept.',
	'usermerge-noolduser' 		=> 'Empty old username',
	'usermerge-olduser' 		=> 'Old user (merge from)',
	'usermerge-newuser' 		=> 'New user (merge to)',
	'usermerge-deleteolduser' 	=> 'Delete old user?',
	'usermerge-submit' 		=> 'Merge user',
	'usermerge-badtoken' 		=> 'Invalid edit token',
	'usermerge-userdeleted' 	=> '$1 ($2) has been deleted.',
	'usermerge-userdeleted-log' 	=> 'Deleted user: $2 ($3)',
	'usermerge-updating' 		=> 'Updating $1 table ($2 to $3)',
	'usermerge-success' 		=> 'Merge from $1 ($2) to $3 ($4) is complete.',
	'usermerge-success-log' 	=> 'User $2 ($3) merged to $4 ($5)',
	'usermerge-logpage'           	=> 'User merge log',
	'usermerge-logpagetext'       	=> 'This is a log of user merge actions',
	'usermerge-noselfdelete'       	=> 'You cannot delete or merge from yourself!',
	'usermerge-unmergable'		=> 'Unable to merge from user - id or name has been defined as unmergable.',
	'usermerge-protectedgroup'	=> 'Unable to merge from user - user is in a protected group.',
);

/** Arabic (العربية)
 * @author Meno25
 */
$messages['ar'] = array(
	'usermerge'                 => 'دمج وحذف المستخدم',
	'usermerge-desc'            => "[[Special:UserMerge|يدمج المراجع من مستخدم إلى آخر]] في قاعدة بيانات الويكي - سيحذف أيضا المستخدمين القدامى بعد الدمج. يتطلب صلاحيات ''usermerge''",
	'usermerge-badolduser'      => 'اسم المستخدم القديم غير صحيح',
	'usermerge-badnewuser'      => 'المستخدم الجديد غير صحيح',
	'usermerge-nonewuser'       => 'اسم مستخدم جديد فارغ - افتراض الدمج إلى $1.<br />اضغط <u>دمج المستخدم</u> للقبول.',
	'usermerge-noolduser'       => 'اسم المستخدم القديم فارغ',
	'usermerge-olduser'         => 'مستخدم قديم(دمج من)',
	'usermerge-newuser'         => 'مستخدم جديد(دمج إلى)',
	'usermerge-deleteolduser'   => 'حذف المستخدم القديم؟',
	'usermerge-submit'          => 'دمج المستخدم',
	'usermerge-badtoken'        => 'نص تعديل غير صحيح',
	'usermerge-userdeleted'     => '$1($2) تم حذفه.',
	'usermerge-userdeleted-log' => 'حذف المستخدم: $2($3)',
	'usermerge-updating'        => 'تحديث $1 جدول ($2 إلى $3)',
	'usermerge-success'         => 'الدمج من $1($2) إلى $3($4) اكتمل.',
	'usermerge-success-log'     => 'المستخدم $2($3) تم دمجه مع $4($5)',
	'usermerge-logpage'         => 'سجل دمج المستخدم',
	'usermerge-logpagetext'     => 'هذا سجل بعمليات دمج المستخدمين',
	'usermerge-noselfdelete'    => 'لا يمكنك حذف أو دمج من نفسك!',
	'usermerge-unmergable'      => 'غير قادر على الدمج من مستخدم - الرقم أو الاسم تم تعريفه كغير قابل للدمج.',
	'usermerge-protectedgroup'  => 'غير قادر على الدمج من المستخدم - المستخدم في مجموعة محمية.',
);

/** Bulgarian (Български)
 * @author DCLXVI
 */
$messages['bg'] = array(
	'usermerge'                 => 'Сливане и изтриване на потребители',
	'usermerge-badolduser'      => 'Невалиден стар потребител',
	'usermerge-badnewuser'      => 'Невалиден нов потребител',
	'usermerge-olduser'         => 'Стар потребител (за сливане от)',
	'usermerge-newuser'         => 'Нов потребител (за сливане в)',
	'usermerge-deleteolduser'   => 'Изтриване на стария потребител?',
	'usermerge-submit'          => 'Сливане',
	'usermerge-userdeleted'     => '$1($2) беше изтрит.',
	'usermerge-userdeleted-log' => 'Изтрит потребител: $2($3)',
	'usermerge-logpage'         => 'Дневник на потребителските сливания',
	'usermerge-logpagetext'     => 'Тази страница съдържа дневник на потребителските сливания',
);

/** Bengali (বাংলা)
 * @author Zaheen
 */
$messages['bn'] = array(
	'usermerge'                 => 'ব্যবহারকারী একত্রীকরণ এবং মুছে ফেলা',
	'usermerge-desc'            => "উইকি ডাটাবেজে [[Special:UserMerge|একজন ব্যবহারকারী থেকে অপর ব্যবহারকারীর প্রতি নির্দেশনাগুলি একত্রিত করে]] - এছাড়া একত্রীকরণের পরে পুরনো ব্যবহারকারীদের মুছে দেবে। বিশেষ ''usermerge'' অধিকার আবশ্যক",
	'usermerge-badolduser'      => 'অবৈধ পুরনো ব্যবহারকারী নাম',
	'usermerge-badnewuser'      => 'অবৈধ নতুন ব্যবহারকারী নাম',
	'usermerge-nonewuser'       => 'খালি নতুন ব্যবহারকারী নাম - $1-এর সাথে একত্রীকরণ করা হচ্ছে ধরা হলে। <br /><u>ব্যবহারকারী একত্রিত করা হোক</u> ক্লিক করে সম্মতি দিন।',
	'usermerge-noolduser'       => 'খালি পুরনো ব্যবহারকারী নাম',
	'usermerge-olduser'         => 'পুরনো ব্যবহারকারী (যার থেকে একত্রীকরণ)',
	'usermerge-newuser'         => 'নতুন ব্যবহারকারী (যার সাথে একত্রীকরণ)',
	'usermerge-deleteolduser'   => 'পুরনো ব্যবহারকারী মুছে ফেলা হোক?',
	'usermerge-submit'          => 'ব্যবহারকারী একত্রিত করা হোক',
	'usermerge-badtoken'        => 'সম্পাদনা টোকেন অবৈধ',
	'usermerge-userdeleted'     => '$1 ($2) মুছে ফেলা হয়েছে।',
	'usermerge-userdeleted-log' => 'ব্যবহারকারী মুছে ফেলে হয়েছে: $2 ($3)',
	'usermerge-updating'        => '$1 টেবিল হালনাগাদ করা হচ্ছে ($2 থেকে $3-তে)',
	'usermerge-success'         => '$1 ($2) থেকে $3 ($4)-তে একত্রীকরণ সম্পন্ন হয়েছে।',
	'usermerge-success-log'     => 'ব্যবহারকারী $2 ($3)-কে $4 ($5)-এর সাথে একত্রিত করা হয়েছে',
	'usermerge-logpage'         => 'ব্যবহারকারী একত্রীকরণ লগ',
	'usermerge-logpagetext'     => 'এটি ব্যবহারকারী একত্রীকরণ ক্রিয়াসমূহের একটি লগ',
	'usermerge-noselfdelete'    => 'আপনি নিজের ব্যবহারকারী নাম মুছে ফেলতে বা এটি থেকে অন্য নামে একত্রিত করতে পারবেন না!',
	'usermerge-unmergable'      => 'ব্যবহারকারী নাম থেকে একত্রিত করা যায়নি - আইডি বা নামটি একত্রীকরণযোগ্য নয় হিসেবে সংজ্ঞায়িত।',
	'usermerge-protectedgroup'  => 'ব্যবহারকারী নাম থেকে একত্রিত করা যায়নি - ব্যবহারকারীটি একটি সুরক্ষিত দলে আছেন।',
);

/** Breton (Brezhoneg)
 * @author Fulup
 */
$messages['br'] = array(
	'usermerge'                 => 'Kendeuziñ an implijer ha diverkañ',
	'usermerge-desc'            => "[[Special:UserMerge|Kendeuziñ a ra daveennoù un implijer gant re unan bennak all]] e diaz titouroù ar wiki - diverkañ a raio ivez ar c'hendeuzadennoù implijer kozh da zont. Rekis eo kaout aotreoù ''kendeuziñ''",
	'usermerge-badolduser'      => 'Anv implijer kozh direizh',
	'usermerge-badnewuser'      => 'Anv implijer nevez direizh',
	'usermerge-nonewuser'       => "Anv implijer nevez goullo - soñjal a ra deomp e fell deoc'h kendeuziñ davet $1.<br />Klikañ war <u>Kendeuziñ implijer</u> evit asantiñ.",
	'usermerge-noolduser'       => 'Anv implijer kozh goullo',
	'usermerge-olduser'         => 'Implijer kozh (kendeuziñ adal)',
	'usermerge-newuser'         => 'Implijer nevez (kendeuziñ davet)',
	'usermerge-deleteolduser'   => 'Diverkañ an implijer kozh ?',
	'usermerge-submit'          => 'Kendeuziñ implijer',
	'usermerge-badtoken'        => 'Jedouer aozañ direizh',
	'usermerge-userdeleted'     => 'Diverket eo bet $1 ($2).',
	'usermerge-userdeleted-log' => 'Implijer diverket : $2($3)',
	'usermerge-updating'        => "Oc'h hizivaat an daolenn $1 (eus $2 da $3)",
	'usermerge-success'         => 'Kendeuzadenn adal $1 ($2) davet $3 ($4) kaset da benn vat.',
	'usermerge-success-log'     => 'Implijer $2 ($3) kendeuzet davet $4 ($5)',
	'usermerge-logpage'         => 'Marilh kendeuzadennoù an implijerien',
	'usermerge-logpagetext'     => 'Setu aze marilh kendeuzadennoù an implijerien',
	'usermerge-noselfdelete'    => "N'hallit ket diverkañ pe kendeuziñ adal pe davedoc'h hoc'h-unan",
	'usermerge-unmergable'      => 'Dibosupl kendeuziñ adal un implijer - un niv. anaout pe un anv bet termenet evel digendeuzadus.',
);

/** German (Deutsch)
 * @author Raimond Spekking
 */
$messages['de'] = array(
	'usermerge'                     => 'Benutzerkonten zusammenführen und löschen',
	'usermerge-desc'                => "[[Special:UserMerge|Führt Benutzerkonten in der Wiki-Datenbank zusammen]] - das alte Benutzerkonto wird nach der Zusammenführung gelöscht. Erfordert das ''usermerge''-Recht.",
	'usermerge-badolduser' 		=> 'Ungültiger alter Benutzername',
	'usermerge-badnewuser' 		=> 'Ungültiger neuer Benutzername',
	'usermerge-nonewuser' 		=> 'Leerer neuer Benutzername - es wird eine Zusammenführung mit $1 vermutet.<br />Klicke <u>Benutzerkonten zusammenführen</u> zum Ausführen.',
	'usermerge-noolduser' 		=> 'Leerer alter Benutzername',
	'usermerge-olduser' 		=> 'Alter Benutzername (zusammenführen von)',
	'usermerge-newuser' 		=> 'Neuer Benutzername (zusammenführen nach)',
	'usermerge-deleteolduser' 	=> 'Alten Benutzernamen löschen?',
	'usermerge-submit' 		=> 'Benutzerkonten zusammenführen',
	'usermerge-badtoken' 		=> 'Ungültiges Bearbeiten-Token',
	'usermerge-userdeleted' 	=> '$1 ($2) wurde gelöscht.',
	'usermerge-userdeleted-log' 	=> 'Gelöschter Benutzername: $2 ($3)',
	'usermerge-updating' 		=> 'Aktualisierung $1 Tabelle ($2 nach $3)',
	'usermerge-success' 		=> 'Die Zusammenführung von $1 ($2) nach $3 ($4) ist vollständig.',
	'usermerge-success-log' 	=> 'Benutzername $2 ($3) zusammengeführt mit $4 ($5)',
	'usermerge-logpage'           	=> 'Logbuch der Benutzerkonten-Zusammenführungen',
	'usermerge-logpagetext'       	=> 'Dies ist das Logbuch der Benutzerkonten-Zusammenführungen.',
	'usermerge-noselfdelete'       	=> 'Zusammenführung mit sich selber ist nicht möglich!',
	'usermerge-unmergable'		=> 'Zusammenführung nicht möglich - ID oder Benutzername wurde als nicht zusammenführbar definiert.',
	'usermerge-protectedgroup'	=> 'Zusammenführung nicht möglich - Benutzername ist in einer geschützen Gruppe.',
);

/** French (Français)
 * @author Sherbrooke
 * @author Grondin
 */
$messages['fr'] = array(
	'usermerge'                 => 'Fusionner utilisateur et détruire',
	'usermerge-desc'            => "[[Special:UserMerge|Fusionne les références d'un utilisatieur vers un autre]] dans la base de donnée wiki - supprimera aussi les anciennes fusions d'utilisateurs suivantes.",
	'usermerge-badolduser'      => "Ancien nom d'utilisateur invalide",
	'usermerge-badnewuser'      => "Nouveau nom d'utilisateur invalide",
	'usermerge-nonewuser'       => "Nouveau nom d'utilisateur vide. Nous faisons l'hypothèse que vous voulez fusionner dans $1.

Cliquez sur ''Fusionner utilisateur'' pour accepter.",
	'usermerge-noolduser'       => "Ancien nom d'utilisateur vide",
	'usermerge-olduser'         => 'Ancien utilisateur (fusionner depuis)',
	'usermerge-newuser'         => 'Nouvel utilisateur(fusionner dans)',
	'usermerge-deleteolduser'   => 'Détruire l’ancien utilisateur ?',
	'usermerge-submit'          => 'Fusionner utilisateur',
	'usermerge-badtoken'        => "Token d'édition invalide",
	'usermerge-userdeleted'     => '$1($2) est détruit.',
	'usermerge-userdeleted-log' => 'Contributeur effacé : $2($3)',
	'usermerge-updating'        => 'Mise à jour de la table $1 (de $2 à $3)',
	'usermerge-success'         => 'La fusion de $1($2) à $3($4) est complétée.',
	'usermerge-success-log'     => 'Contributeur $2($3) fusionné avec $4($5)',
	'usermerge-logpage'         => 'Journal des fusions de contributeurs',
	'usermerge-logpagetext'     => 'Ceci est un journal des actions de fusions de contributeurs',
	'usermerge-noselfdelete'    => 'Vous ne pouvez pas, vous-même, vous supprimer ni vous fusionner !',
	'usermerge-unmergable'      => "Ne peut fusionner à partir d'un utilisateur, d'un numéro d'identification ou un nom qui ont été définis comme non fusionnables.",
	'usermerge-protectedgroup'  => "Impossible de fusionner à partir d'un utilisateur - l'utilisateur se trouve dans un groupe protégé.",
);

/** Galician (Galego)
 * @author Alma
 */
$messages['gl'] = array(
	'usermerge'                 => 'Fusionar e Eliminar usuario',
	'usermerge-badolduser'      => 'Antigo Nome de usuario non válido',
	'usermerge-badnewuser'      => 'Novo Usuario non válido',
	'usermerge-nonewuser'       => 'Novo Nome de usuario baleiro - Asumindo que se fusionan para $1.<br />Prema <u>Fusionar Usuario</u> para aceptar.',
	'usermerge-noolduser'       => 'Antigo Nome de usuario baleiro',
	'usermerge-olduser'         => 'Antigo Usuario(Fusionar Para)',
	'usermerge-newuser'         => 'Novo Usuario(Fusionar A)',
	'usermerge-deleteolduser'   => 'Eliminar Antigo Usuario?',
	'usermerge-submit'          => 'Fusionar Usuario',
	'usermerge-badtoken'        => 'Sinal de Edición non válida',
	'usermerge-userdeleted'     => '$1($2) foi eliminado.',
	'usermerge-userdeleted-log' => 'Eliminado usuario: $2($3)',
	'usermerge-updating'        => 'Actualizando táboa $1 ($2 a $3)',
	'usermerge-success'         => 'A fusión de $1($2) a $3($4) está completa.',
	'usermerge-success-log'     => 'Usuario $2($3) fusionado a $4($5)',
	'usermerge-logpage'         => 'Rexistro da Fusión de usuarios',
	'usermerge-logpagetext'     => 'Este é un rexistro das accións de fusión de usuarios',
);

$messages['hsb'] = array(
	'usermerge' => 'Wužiwarske konta zjednoćić a zničić',
	'usermerge-badolduser' => 'Njepłaćiwe stare wužiwarske mjeno',
	'usermerge-badnewuser' => 'Njepłaćiwe nowe wužiwarske mjeno',
	'usermerge-nonewuser' => 'Nowe wužiwarske mjeno faluje - najskerje ma so z $1 zjednoćić.<br /> Klikń na <u>Wužiwarske konta zjednoćić</u>, zo by potwerdźił.',
	'usermerge-noolduser' => 'Falowace stare wužiwarske mjeno',
	'usermerge-olduser' => 'Stare wužiwarske konto (Zjednoćić wot)',
	'usermerge-newuser' => 'Nowe wužiwarske konto (Zjednoćić do)',
	'usermerge-deleteolduser' => 'Stare wužiwarske mjeno zničić?',
	'usermerge-submit' => 'Wužiwarske konta zjednoćić',
	'usermerge-badtoken' => 'Njepłaćiwe wobdźěłanske znamjo',
	'usermerge-userdeleted' => '$1($2) bu zničeny.',
	'usermerge-userdeleted-log' => 'Wušmórnjeny wužiwar: $2($3)',
	'usermerge-updating' => '$1 tabela so aktualizuje ($2 do $3)',
	'usermerge-success' => 'Zjednoćenje wot $1($2) do $3($4) je dokónčene.',
	'usermerge-success-log' => 'Wužiwar $2($3) je so z $4 ($5) zjednoćił',
	'usermerge-logpage' => 'Protokol wužiwarskich zjednoćenjow',
	'usermerge-logpagetext' => 'To je protokol wužiwarskich zjednoćenjow',
);

/** Khmer (ភាសាខ្មែរ)
 * @author Chhorran
 */
$messages['km'] = array(
	'usermerge-badolduser'      => 'ឈ្មោះអ្នកប្រើប្រាស់ ចាស់ គ្មានសុពលភាព',
	'usermerge-badnewuser'      => 'ឈ្មោះអ្នកប្រើប្រាស់ ថ្មី គ្មានសុពលភាព',
	'usermerge-deleteolduser'   => 'លុបចេញ អ្នកប្រើប្រាស់ ចាស់ ឬ ?',
	'usermerge-userdeleted-log' => 'អ្នកប្រើប្រាស់ ត្រូវបានលុបចេញ ៖ $2 ($3)',
	'usermerge-noselfdelete'    => 'អ្នកមិនអាច លុបចេញ ឬ បញ្ចូលរួមគ្នា ពីខ្លួនអ្នកផ្ទាល់ !',
);

/** Luxembourgish (Lëtzebuergesch)
 * @author Robby
 */
$messages['lb'] = array(
	'usermerge-userdeleted-log' => 'Geläschte Benotzer: $2($3)',
);

/** Dutch (Nederlands)
 * @author SPQRobin
 * @author Siebrand
 */
$messages['nl'] = array(
	'usermerge'                 => 'Gebruikers samenvoegen en verwijderen',
	'usermerge-desc'            => "Voegt een [[Special:UserMerge|speciale pagina]] toe om gebruikers samen te voegen en de oude gebruiker(s) te verwijderen (hiervoor is het recht ''usermerge'' nodig)",
	'usermerge-badolduser'      => 'Verkeerde oude gebruiker',
	'usermerge-badnewuser'      => 'Verkeerde nieuwe gebruiker',
	'usermerge-nonewuser'       => 'Nieuwe gebruiker is niet ingegeven - er wordt aangenomen dat er samengevoegd moet worden naar $1.<br />Klik <u>Gebruiker samenvoegen</u> om te aanvaarden.',
	'usermerge-noolduser'       => 'Oude gebruiker is niet ingegeven',
	'usermerge-olduser'         => 'Oude gebruiker (samenvoegen van)',
	'usermerge-newuser'         => 'Nieuwe gebruiker (samenvoegen naar)',
	'usermerge-deleteolduser'   => 'Oude gebruiker verwijderen?',
	'usermerge-submit'          => 'Gebruiker samenvoegen',
	'usermerge-badtoken'        => 'Ongeldig bewerkingstoken',
	'usermerge-userdeleted'     => '$1($2) is verwijderd.',
	'usermerge-userdeleted-log' => 'Verwijderde gebruiker: $2($3)',
	'usermerge-updating'        => 'Tabel $1 aan het bijwerken ($2 naar $3)',
	'usermerge-success'         => 'Samenvoegen van $1($2) naar $3($4) is afgerond.',
	'usermerge-success-log'     => 'Gebruiker $2($3) samengevoegd naar $3($5)',
	'usermerge-logpage'         => 'Logboek gebruikerssamenvoegingen',
	'usermerge-logpagetext'     => 'Dit is het logboek van gebruikerssamenvoegingen.',
	'usermerge-noselfdelete'    => 'U kan uzelf niet verwijderen of samenvoegen!',
	'usermerge-unmergable'      => 'Deze gebruiker kan niet samengevoegd worden. De gebruikersnaam of het gebruikersnummer is ingesteld als niet samen te voegen.',
	'usermerge-protectedgroup'  => 'Het is niet mogelijk de gebruikers samen te voegen. De gebruiker zit in een beschermde groep.',
);

/** Norwegian (‪Norsk (bokmål)‬)
 * @author Jon Harald Søby
 */
$messages['no'] = array(
	'usermerge'                 => 'Brukersammenslåing og -sletting',
	'usermerge-desc'            => "Gir muligheten til  å [[Special:UserMerge|slå sammen kontoer]] ved at alle referanser til en bruker byttes ut til en annen bruker i databasen, for så å slette den ene kontoen. Trenger rettigheten ''usermerge''.",
	'usermerge-badolduser'      => 'Gammelt brukernavn ugyldig',
	'usermerge-badnewuser'      => 'Nytt brukernavn ugyldig',
	'usermerge-nonewuser'       => 'Nytt brukernavn tomt &ndash; antar sammenslåing til $1.<br />Klikk <u>Slå sammen brukere</u> for å godta.',
	'usermerge-noolduser'       => 'Gammelt brukernavn tomt',
	'usermerge-olduser'         => 'Gammelt brukernavn (slå sammen fra)',
	'usermerge-newuser'         => 'Nytt brukernavn (slå sammen til)',
	'usermerge-deleteolduser'   => 'Slett gammel bruker?',
	'usermerge-submit'          => 'Slå sammen brukere',
	'usermerge-badtoken'        => 'Ugydlgi redigeringstegn',
	'usermerge-userdeleted'     => '$1 ($2) har blitt slettet.',
	'usermerge-userdeleted-log' => 'Slettet bruker: $2 ($3)',
	'usermerge-updating'        => 'Oppdaterer $1-tabell ($2 til $3)',
	'usermerge-success'         => 'Sammenslåing fra $1 ($2) til $3 ($4) er ferdig.',
	'usermerge-success-log'     => 'Brukeren $2 ($3) slått sammen med $4 ($5)',
	'usermerge-logpage'         => 'Brukersammenslåingslogg',
	'usermerge-logpagetext'     => 'Dette er en logg over brukersammenslåinger.',
	'usermerge-noselfdelete'    => 'Du kan ikke slette eller slå sammen din egen konto!',
	'usermerge-unmergable'      => 'Kan ikke slå sammen den gamle kontoen. ID-en eller navnet anses som ikke-sammenslåbart.',
	'usermerge-protectedgroup'  => 'Kan ikke slå sammen den gamle kontoen. Brukeren er medlem i en beskyttet brukergruppe.',
);

/** Occitan (Occitan)
 * @author Cedric31
 */
$messages['oc'] = array(
	'usermerge'                 => 'Fusionar utilizaire e destruire',
	'usermerge-desc'            => "[[Special:UserMerge|Fusiona las referéncias d'un utilizaire vèrs un autre]] dins la banca de donadas wiki - suprimirà tanben las fusions d'utilizaires ancianas seguentas.",
	'usermerge-badolduser'      => "Nom d'utilizaire ancian invalid",
	'usermerge-badnewuser'      => "Nom d'utilizaire novèl invalid",
	'usermerge-nonewuser'       => "Nom d'utilizaire novèl void. Fasèm l'ipotèsi que volètz fusionar dins $1. Clicatz sus ''Fusionar utilizaire'' per acceptar.",
	'usermerge-noolduser'       => "Nom d'utilizaire ancian void",
	'usermerge-olduser'         => 'Utilizaire ancian (fusionar dempuèi)',
	'usermerge-newuser'         => 'Utilizaire novèl (fusionar dins)',
	'usermerge-deleteolduser'   => 'Destruire utilizaire ancian ?',
	'usermerge-submit'          => 'Fusionar utilizaire',
	'usermerge-badtoken'        => "Token d'edicion invalid",
	'usermerge-userdeleted'     => '$1($2) es destruch.',
	'usermerge-userdeleted-log' => 'Contributor escafat : $2($3)',
	'usermerge-updating'        => 'Mesa a jorn de la taula $1 (de $2 a $3)',
	'usermerge-success'         => 'La fusion de $1($2) a $3($4) es completada.',
	'usermerge-success-log'     => 'Contributor $2($3) fusionat amb $4($5)',
	'usermerge-logpage'         => 'Jornal de las fusions de contributors',
	'usermerge-logpagetext'     => 'Aquò es un jornal de las accions de fusions de contributors',
	'usermerge-noselfdelete'    => 'Podètz pas, vos-meteis, vos suprimir ni vos fusionar !',
	'usermerge-unmergable'      => "Pòt pas fusionar a partir d'un utilizaire, d'un numèro d'identificacion o un nom que son estats definits coma non fusionables.",
	'usermerge-protectedgroup'  => "Impossible de fusionar a partir d'un utilizaire - l'utilizaire se tròba dins un grop protegit.",
);

$messages['pms'] = array(
	'usermerge' => 'Union e scancelament d\'utent',
	'usermerge-badolduser' => 'Vej stranòm nen bon',
	'usermerge-badnewuser' => 'Neuv stranòm nen bon',
	'usermerge-nonewuser' => 'Neuv stranòm veujd - i la tnisoma bon për n\'union a $1.<br />de-ie \'n colp ansima a <u>Unì Utent</u> për aceté.',
	'usermerge-noolduser' => 'Vej stranòm veujd',
	'usermerge-olduser' => 'Vej stranòm (Unì da)',
	'usermerge-newuser' => 'Neuv stranòm (Unì a)',
	'usermerge-deleteolduser' => 'Veul-lo scancelé l\'utent vej?',
	'usermerge-submit' => 'Unì Utent',
	'usermerge-badtoken' => 'Geton d\'edission nen bon',
	'usermerge-userdeleted' => '$1($2) a l\'é stàit scancelà.',
	'usermerge-updating' => 'Antramentr ch\'i agiornoma la tàola $1 ($2 a $3)',
	'usermerge-success' => 'Union da $1($2) a $3($4) completà.',
);

/** Portuguese (Português)
 * @author Malafaya
 */
$messages['pt'] = array(
	'usermerge-deleteolduser' => 'Apagar Utilizador Antigo?',
);

/** Slovak (Slovenčina)
 * @author Helix84
 */
$messages['sk'] = array(
	'usermerge'                 => 'Zlúčenie a zmazanie používateľov',
	'usermerge-desc'            => "[[Special:UserMerge|Zlučuje odkazy na jedného používateľa na odkazy na druhého]] v databáze wiki; tiež následne zmaže starého používateľa. Vyžaduje oprávnenie ''usermerge''.",
	'usermerge-badolduser'      => 'Neplatné staré používateľské meno',
	'usermerge-badnewuser'      => 'Neplatné nové používateľské meno',
	'usermerge-nonewuser'       => 'Prázdne nové používateľské meno - Predpokladá sa zlúčenie do $1.<br />Kliknutím na <u>Zlúčiť používateľov</u> prijmete.',
	'usermerge-noolduser'       => 'Prázdne staré používateľské meno',
	'usermerge-olduser'         => 'Starý používateľ(zlúčiť odtiaľto)',
	'usermerge-newuser'         => 'Nový používate(zlúčiť sem)',
	'usermerge-deleteolduser'   => 'Zmazať starého používateľa?',
	'usermerge-submit'          => 'Zlúčiť používateľov',
	'usermerge-badtoken'        => 'Neplatný token úprav',
	'usermerge-userdeleted'     => '$1($2) bol zmazaný.',
	'usermerge-userdeleted-log' => 'Zmazaný používateľ: $2($3)',
	'usermerge-updating'        => 'Aktualizuje sa tabuľka $1 ($2 na $3)',
	'usermerge-success'         => 'Zlúčenie z $1($2) do $3($4) je dokončené.',
	'usermerge-success-log'     => 'Používateľ $2($3) bol zlúčený do $4($5)',
	'usermerge-logpage'         => 'Záznam zlúčení používateľov',
	'usermerge-logpagetext'     => 'Toto je záznam zlúčení používateľov',
	'usermerge-noselfdelete'    => 'Nemôžete zmazať alebo zlúčiť svoj účet!',
	'usermerge-unmergable'      => 'Nebolo možné vykonať zlúčenie používateľa - zdrojové meno alebo ID bolo definované ako nezlúčiteľné.',
	'usermerge-protectedgroup'  => 'Nebolo možné zlúčiť uvedeného používateľa - používateľ je v chránenej skupine.',
);

/** Seeltersk (Seeltersk)
 * @author Pyt
 */
$messages['stq'] = array(
	'usermerge' => 'Benutserkonten touhoopefiere un läskje',
);

/** Swedish (Svenska)
 * @author Lejonel
 */
$messages['sv'] = array(
	'usermerge'                 => 'Slå ihop och radera användarkonton',
	'usermerge-desc'            => "Ger möjlighet att [[Special:UserMerge|slå samman användarkonton]] genom att alla referenser till en användare byts ut till en annan användare i databasen, samt att efter sammanslagning radera gamla konton. Kräver behörigheten ''usermerge''.",
	'usermerge-badolduser'      => 'Ogiltigt gammalt användarnamn',
	'usermerge-badnewuser'      => 'Ogiltigt nytt användarnamn',
	'usermerge-nonewuser'       => 'Inget nytt användarnamn angavs. Antar att det gamla kontot ska slås ihop till $1.<br />Tryck på <u>Slå ihop konton</u> för att godkänna sammanslagningen.',
	'usermerge-noolduser'       => 'Inget gammalt användarnamn angavs',
	'usermerge-olduser'         => 'Gammalt användarnamn (slås ihop från)',
	'usermerge-newuser'         => 'Nytt användarnamn (slås ihop till)',
	'usermerge-deleteolduser'   => 'Ta bort det gamla användarkontot?',
	'usermerge-submit'          => 'Slå ihop konton',
	'usermerge-badtoken'        => 'Ogiltigt redigerings-token',
	'usermerge-userdeleted'     => '$1 ($2) har raderats.',
	'usermerge-userdeleted-log' => 'raderade användare $2 ($3)',
	'usermerge-updating'        => 'Uppdaterar tabellen $1 (från $2 till $3)',
	'usermerge-success'         => 'Sammanslagningen av $1 ($2) till $3 ($4) har genomförts.',
	'usermerge-success-log'     => 'sammanfogade användare $2 ($3) till $4 ($5)',
	'usermerge-logpage'         => 'Användarsammanslagningslogg',
	'usermerge-logpagetext'     => 'Det här är en logg över sammanslagningar av användarkonton.',
	'usermerge-noselfdelete'    => 'Du kan inte radera eller slå samman ditt eget konto!',
	'usermerge-unmergable'      => 'Kan inte sammanfoga det gamla kontot. ID:t eller namnet har angetts som icke-sammanslagningsbart.',
	'usermerge-protectedgroup'  => 'Kan inte sammanfoga det gamla kontot. Användaren är medlem i en skyddad användargrupp.',
);

/** Turkish (Türkçe)
 * @author Karduelis
 */
$messages['tr'] = array(
	'usermerge-badolduser'    => 'Geçersiz eski kullanıcı adı',
	'usermerge-badnewuser'    => 'Geçersiz yeni kullanıcı',
	'usermerge-noolduser'     => 'Boş eski kullanıcı adı',
	'usermerge-deleteolduser' => 'Eski kullanıcı sil ?',
);

$messages['yue'] = array(
	'usermerge' => '用戶合併同刪除',
	'usermerge-badolduser' => '無效嘅舊用戶名',
	'usermerge-badnewuser' => '無效嘅新用戶名',
	'usermerge-nonewuser' => '清除新用戶名 - 假設合併到$1。<br />撳<u>合併用戶</u>去接受。',
	'usermerge-noolduser' => '清除舊用戶名',
	'usermerge-olduser' => '舊用戶 (合併自)',
	'usermerge-newuser' => '新用戶 (合併到)',
	'usermerge-deleteolduser' => '刪舊用戶？',
	'usermerge-submit' => '合併用戶',
	'usermerge-badtoken' => '無效嘅編輯幣',
	'usermerge-userdeleted' => '$1($2) 已經刪除咗。',
	'usermerge-updating' => '更新緊 $1 表 ($2 到 $3)',
	'usermerge-success' => '由 $1($2) 到 $3($4) 嘅合併已經完成。'
);

$messages['zh-hans'] = array(
	'usermerge' => '用户合并和删除',
	'usermerge-badolduser' => '无效的旧用户名',
	'usermerge-badnewuser' => '无效的新用户名',
	'usermerge-nonewuser' => '清除新用户名 - 假设合并到$1。<br />点击<u>合并用户</u>以接受。',
	'usermerge-noolduser' => '清除旧用户名',
	'usermerge-olduser' => '旧用户 (合并自)',
	'usermerge-newuser' => '新用户 (合并到)',
	'usermerge-deleteolduser' => '删除旧用户？',
	'usermerge-submit' => '合并用户',
	'usermerge-badtoken' => '无效的编辑币',
	'usermerge-userdeleted' => '$1($2) 已删除。',
	'usermerge-updating' => '正在更新 $1 表格 ($2 到 $3)',
	'usermerge-success' => '由 $1($2) 到 $3($4) 的合并已经完成。'
);

$messages['zh-hant'] = array(
	'usermerge' => '用戶合併和刪除',
	'usermerge-badolduser' => '無效的舊用戶名',
	'usermerge-badnewuser' => '無效的新用戶名',
	'usermerge-nonewuser' => '清除新用戶名 - 假設合併到$1。<br />點擊<u>合併用戶</u>以接受。',
	'usermerge-noolduser' => '清除舊用戶名',
	'usermerge-olduser' => '舊用戶 (合併自)',
	'usermerge-newuser' => '新用戶 (合併到)',
	'usermerge-deleteolduser' => '刪除舊用戶？',
	'usermerge-submit' => '合併用戶',
	'usermerge-badtoken' => '無效的編輯幣',
	'usermerge-userdeleted' => '$1($2) 已刪除。',
	'usermerge-updating' => '正在更新 $1 表格 ($2 到 $3)',
	'usermerge-success' => '由 $1($2) 到 $3($4) 的合併已經完成。'
);

