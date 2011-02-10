
function setImages(group)
{
	// If the images haven't finished loading, just return
	if (!group.IsComplete())
		return;

	// A set of images has just loaded; so go through the image set
	// and reconnect them with the document
	for (i = 0; i < group.items.length; i ++)
	{
		var img_name = group.items[i];
		var img_obj = GetImage(img_name);
		
		// Make the image visible, and set its .src attribute
		eval("document.images['" + img_name + "'].style.display = ''");
		eval("document.images['" + img_name + "']").src = img_obj.src;
	}
}

function loadImages()
{
	var group = new ImageGroup();
	
	// Add all the images on the page that have a "name" attribute
	for (i = 0; i < document.images.length; i ++)
	{
		if (document.images[i].name)
		{
			// Add each image to the group, and hide it until it's loaded
			group.AddImage(document.images[i].name);
			document.images[i].style.display = "none";
		}
	}
	
	// Set the completion handler, and load the group
	group.imageupdate = setImages;
	LoadGroup(group);
}

