var mcePath = '../../..';

var InternalImageDialog = {
	insert : function( filename ) {
		// Insert the contents from the input into the document
		tinyMCEPopup.editor.execCommand('mceInsertContent', false, '<img src="' + filename + '" />');
		tinyMCEPopup.close();
	},
	
	preview : function( filename, id ) {
		document.getElementById(id).innerHTML = '<img src="'+filename+'" />';
	}
};
