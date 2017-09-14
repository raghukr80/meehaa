[[Category:Modules compl�mentaires]]
[[Category:CustomFields]]
{{TemplateDocUtil}}
{{TemplateModFR}}
L'article n'a pas encore �t� enti�rement traduit en fran�ais.

Veuillez [[Module_CustomFields|lire le wiki en anglais]] qui est d�j� complet (ic�ne � gauche).

{{ToTranslate}}

= Informations =
{{TemplateModuleInfo
|editor=
|web=
|webbuy={{LinkToPluginDownloadDoliStore|keyword=customfield}}
|status=stable
|prerequisites=Dolibarr <= 3.3.*
|minversion=3.2.0
|note=
}}

= Utilisation =
== Traduction du libell� d'un champ ==

Les champs peuvent �tre facilement renomm� ou traduit dans plusieurs langues en �ditant les fichiers de langues.

Ouvrez le fichier /customfields/langs/code_CODE/customfields-user.lang (o� code_CODE est le code ISO de votre r�gion, ex: en_US ou fr_FR) et ajoutez dedans le nom de la Variable de votre champ personnalis� (affich� dans le panneau administrateur, colonne Variable) suivi de la traduction (format: cf_monchamp= Mon Libell�).

Ex: disons que votre champ personnalis� est nomm� "user_ref", et que le nom de Variable r�sultat est "cf_user_ref". Dans customfields-user.lang il vous suffit d'ajouter:
<pre>
cf_user_ref= Le libell� que vous voulez. Vous pouvez m�me �crire une tr�s tr�s longue phrase ici.<br />Et vous pouvez m�me ins�rer des retours � la ligne avec <br />.
</pre>

== Testez vos champs personnalis�s avec le module PDFTest ==

Un module auxiliare appel� CustomFieldsPDFTest est fourni afin que que vous puissiez facilement et rapidement tester vos champs personnalis�s dans vos documents PDF. Cela �vite d'avoir � faire votre propre mod�le PDF juste pour tester et risquer de faire des erreurs de code php.

Il suffit juste d'activer le module CustomFieldsPDFTest dans Accueil>Configuration>Modules et ensuite de g�n�rer un fichier PDF en utilisant n'importe quel mod�le.

Une page sera rajout� � la fin du fichier PDF g�n�r�, contenant une liste extensive de tous les champs personnalis�s disponibles ainsi que leurs valeurs, et leurs valeurs brut(=raw) (valeur raw = pas de beautification, pas d'encode html ni de traduction).

Vous pouvez ainsi v�rifier qu'un champ personnalis� correspond bien � vos besoins et d�livre toutes les informations dont vous aurez besoin dans votre futur mod�le PDF.

Quand vous avez fini le test, d�sactivez simplement le module, vous ferez votre propre mod�le PDF (voir ci-dessous)

Note: les documents PDF d�j� g�n�r�s ne seront pas affect�s, seulement les documents g�n�r�s '''apr�s l'activation du module PDFTest''' se verront octroy�s cette page suppl�mentaire de champs personnalis�s, et apr�s d�sactivation du module, si vous g�n�rez � nouveau le document PDF, les pages suppl�mentaires dispara�trons.

== Impl�mentation dans les mod�les ODT ==

Les champs personnalis�s sont automatiquement charg�s pour les mod�les ODT sans op�ration suppl�mentaire.

Utilisez juste le nom de la Variable (colonne '''Variable''' dans le panneau admin) comme un tag, enclos� de deux accolades.

Ex: pour un champ personnalis� nomm� user_ref, vous obtiendrez comme nom de Variable cf_user_ref. Dans votre ODT, pour obtenir la valeur de ce champ, il suffit de faire:
<pre>
{cf_user_ref}
</pre>

Vous pouvez �galement obtenir la valeur brute (sans aucun pr�-traitement) en ajoutant le suffixe _raw au nom de variable:
<pre>
{cf_user_ref_raw}
</pre>

Il y a �galement un support complet des champs contraints, ce qui fait que si vous avez une contrainte sur ce champ, les valeurs li�es dans la table r�f�renc�e seront automatiquement r�cup�r�es et vous serez en mesure de les utiliser avec de simples tags.

Ex: cf_user_ref est contraint sur la table '''llx_user''':
<pre>
{cf_user_ref} = rowid
{cf_user_ref_firstname} = firstname
{cf_user_ref_user_mobile} = mobile phone
etc...
</pre>

Comme vous pouvez le voir, il suffit de rajouter le suffixe '_' et le nom de la colonne sql dont vous voulez obtenir la valeur.

Pour les lignes produits, cela fonctionne de la m�me fa�on, il suffit d'�crire le nom de Variable dans la table des lignes produits, entre les tags [!-- BEGIN row.lines --] et [!-- END row.lines --]

Note: un usage int�ressant des champs personnalis�s est d'utiliser un type Vrai/Faux avec une substitution conditionnelle, ex: avec un champ personnalis� cf_enablethis:
<pre>
[!-- IF {cf_enablethis_raw} --]
Ce texte s'affichera si cf_enablethis est Vrai
[!-- ELSE {cf_enablethis_raw} --]
Sinon, ce texte ci s'affichera si cf_enablethis est Faux
[!-- ENDIF {cf_enablethis_raw} --]
</pre>
Il est n�cessaire d'utiliser la valeur brute, car il est fiable d'avoir une valeur 0/1 pour que la condition fonctionne. Sinon on peut aussi avoir vide/non-vide, ce qui fait que cette technique fonctionne aussi pour les types Text ou tout autre: si le texte est vide, vous pouvez ne rien afficher, par contre si le texte n'est pas vide vous pouvez mettre un pr�ambule et la valeur du champ:
<pre>
[!-- IF {cf_mytextfield_raw} --]
Mon champ texte n'est pas vide, voici sa valeur: {cf_mytextfield}
[!-- ENDIF {cf_mytextfield_raw} --]
</pre>

== Impl�mentation dans les mod�les PDF ==

Pour utiliser vos champs personnalis�s dans votre mod�le PDF, vous devez tout d'abord charger les donn�es des champs personnalis�s, ensuite vous pourrez les utiliser comme bon vous semble.

* Pour charger les champs personnalis�s:
Placer le code suivant le plus haut possible dans votre mod�le PDF:
<source lang="php">
// Init and main vars for CustomFields
dol_include_once('/customfields/lib/customfields_aux.lib.php');

// Filling the $object with customfields (you can then access customfields by doing $object->customfields->cf_yourfield)
$this->customfields = customfields_fill_object($object, null, $outputlangs, null, true); // beautified values
$this->customfields_raw = customfields_fill_object($object, null, $outputlangs, 'raw', null); // raw values
$this->customfields_lines = customfields_fill_object_lines($object, null, $outputlangs, null, true); // product lines' values
</source>

Note: vous pouvez placer le code au-dessus juste en-dessous de cette ligne dans les mod�les PDF:
<source lang="php">
$pdf=pdf_getInstance($this->format);
</source>

* Pour acc�der � la valeur du champ personnalis�:

Formattage beautifi�:
<source lang="php">
$object->customfields->cf_myfield
</source>
ou pour la valeur brute:
<source lang="php">
$object->customfields->raw->cf_myfield
</source>

* Pour acc�der aux champs personnalis�s des lignes produits:
<source lang="php">
$lineid = $object->lines[$i]->rowid;
$object->customfields->lines->$lineid->cf_myfield
</source>
O� $lineid doit �tre remplac� par l'id de la ligne produit que vous voulez r�cup�rer (rowid sql des produits, donc �a ne commence pas forc�ment par 0 et peut �tre n'importe quel nombre).

* Pour imprimer le champ dans votre PDF avec FPDF (librairie PDF par d�faut):
<source lang="php">
$pdf->MultiCell(0,3, $object->customfields->cf_myfield, 0, 'L'); // printing the customfield
</source>

* Et si vous souhaitez imprimer le libell� en multilangue:
<source lang="php">
$outputlangs->load('customfields-user@customfields');
$mylabel = $customfields->findLabel("cf_myfield", $outputlangs); // where $outputlangs is the language the PDF should be outputted to
</source>
ou si vous souhaitez le faire automatiquement (utile dans une boucle):
<source lang="php">
$outputlangs->load('customfields-user@customfields');
$keys=array_keys(get_object_vars($object->customfields));
$mylabel = $outputlangs->trans($keys[xxx]); // where xxx is a number, you can iterate foreach($keys as $key) if you prefer
</source>

== Impl�mentation en code php (module core Dolibarr ou pour vos propres modules) ==

Une des fonctionnalit�s principales du module CustomFields est qu'il offre un moyen g�n�rique d'acc�der, d'ajouter, de modifier et d'afficher des champs personnalis�s depuis votre propre code. Vous pouvez facilement d�velopper votre propre module en utilisant uniquement des champs bas�s sur la classe CustomFields.

Pour r�cup�rer les valeurs des champs, vous pouvez utiliser la librairie simplificatrice qui facilite beaucoup l'utilisation des champs personnalis�s vos codes php:
<source lang="php">
dol_include_once('/customfields/lib/customfields_aux.lib.php'); // include the simplifier library
$customfields = customfields_fill_object($object, null, $langs); // load the custom fields values inside $object->customfields
</source>

Vous pouvez alors facilement acc�der aux valeurs des champs personnalis�s comme ceci:
<source lang="php">
print($object->customfields->cf_myfield);
</source>

Pour charger les champs personnalis�s des lignes produits, vous pouvez utiliser la fonction customfields_fill_object_line():
<source lang="php">
dol_include_once('/customfields/lib/customfields_aux.lib.php'); // include the simplifier library
$customfields = customfields_fill_object_lines($object, null, $langs); // load the custom fields values inside $object->customfields
</source>

Vous pouvez alors acc�der aux champs des lignes produits comme ceci:
<source lang="php">
$object->customfields->lines->$lineid->cf_myfield
</source>

Vous pouvez �galement obtenir (et bien plus) manuellement les valeurs des champs personnalis�s en utilisant la classe CustomFields:

<source lang="php">
// Init and main vars
//include_once(DOL_DOCUMENT_ROOT.'/customfields/class/customfields.class.php'); // OLD WAY
dol_include_once('/customfields/class/customfields.class.php'); // NEW WAY since Dolibarr v3.3
$customfields = new CustomFields($this->db, $currentmodule); // where $currentmodule is the current module, you can replace it by '' if you just want to use printing functions and fetchAny.

//$records = $customfields->fetchAll(); // to fetch all records
$records = $customfields->fetch($id); // to fetch one object's records
</source>
