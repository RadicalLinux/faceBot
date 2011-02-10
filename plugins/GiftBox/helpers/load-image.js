
// Globals
var _g_image_cache = new ImageCache();
var _g_image_group = null;

// Top-level methods to provide cache access without
// the need to refer explicitly to global variables
function LoadGroup(group)
{
	_g_image_cache.LoadGroup(group);
}

function GetImage(name)
{
	return _g_image_cache.GetImage(name);
}
		
function loadImage_callback()
{
	// The loading state of some image has just updated
	var obj = event.srcElement;
	var group = _g_image_group;

	// If the image has just loaded, update the count
	if (obj.readyState == "complete")
		group.numLoaded ++;
	
	// Call imageupdate handler on group, if specified;
	// otherwise call imageupdate handler on document
	if (group.imageupdate)
		eval("group.imageupdate(group)");
	else if (document.imageupdate)
		eval("document.imageupdate(group)");

	// If there are still images to load, schedule another callback
	// (condition isn't strictly necessary, since "complete" is the
	// final stage of loading, therefore when an image is "complete"
	// it won't fire further readystatechange events)
	if (group.numLoaded != group.numToLoad)
		obj.onreadystatechange = loadImage_callback;
}

function ImageGroup()
{
	this.AddImage = _addImage;
	this.IsComplete = _isComplete;
	this.imageupdate = null;

	this.items = new Array();
	this.numLoaded = 0;
	this.numToLoad = 0;

	return this;
}

function _addImage(path)
{
	this.items[ this.items.length ] = path;
}

function _isComplete()
{
	return this.numLoaded == this.numToLoad;
}

function ImagePair( _file )
{
	// The ImagePair object's purpose is to
	// keep track of the image and the file that identifies it;
	// we do this because the image's src attribute will read back
	// as uuencoded if interrogated, meaning that, to see if a
	// particular image exists in the cache, we need to uuencode
	// again. It seems easier just to store the path in unencoded
	// form, along with the image associated with it.
	this.file = _file;
	this.image = new Image();
	
	// Add the image to the document; if you don't, readystatechange
	// events won't know where they originated, meaning that
	// event.srcElement will be null
	document.appendChild(this.image);
}

function ImageCache()
{
	this.LoadGroup = _loadGroup;
	this.GetImage = _getImage;
	
	this.items = new Array();
	return this;
}
	
function _loadGroup( group )
{
	// If there aren't any images to load, do nothing
	if (group.items.length == 0)
		return;
	
	// Set the active group
	_g_image_group = group;
	
	// Reset the count of loaded images and images to load
	group.numLoaded = 0;
	group.numToLoad = group.items.length;

	// num_loaded, like group.numLoaded, is the count of images
	// in the group that have completed loading. However, we
	// only update group.numLoaded after having kicked off all
	// the new images we want to load. Otherwise race conditions
	// can lead to imageupdate getting called more than once after
	// all the images are loaded.
	var num_loaded = 0;
	
	// Add any of the images to the cache if they don't yet exist
	for (i = 0; i < group.numToLoad; i ++)
	{
		var count = this.items.length;
	
		for (j = 0; j < count; j++)
		{
			if (this.items[j].file.indexOf(group.items[i]) != -1)
			{
				break;
			}
		}
		
		// Did we find it?
		if (j == count)
		{
			// No, so add it and start loading. Assignment order is
			// critical in the next few lines. Ensure the image is
			// added to the cache before setting its .src attribute;
			// otherwise for a short time an image group may affirm
			// itself "loaded" before all its elements exist in
			// the cache
			var pair = new ImagePair(group.items[i]);
			this.items[this.items.length] = pair;
			(pair.image).onreadystatechange = loadImage_callback;
			pair.image.src = group.items[i];
		}
		else
		{
			// Yes, but check whether it's finished loading
			if (this.items[j].image.readyState == "complete")
			{
				num_loaded ++;
			}
		}
	}
	
	// Check whether the group has already completely loaded
	if (num_loaded == group.numToLoad)
	{
		// Yes it has; it's safe to set group.numLoaded now,
		// because we know, since all the images are fuly loaded,
		// there won't be any more readystatechange events
		group.numLoaded = num_loaded;

		// Invoke the group's update handler, if specified;
		// otherwise invoke the document's update handler
		if (group.imageupdate)
			eval("group.imageupdate(group)");
		else if (document.imageupdate)
			eval("document.imageupdate(group)");
	}
	
	// We can now throw away num_loaded; its only purpose was to
	// see if everything was already loaded, since, if it was, no
	// readystatechange events would be generated, and therefore
	// no imageupdate events, and therefore no notification that
	// loading was already complete. We do NOT want to update
	// group.numLoaded, since num_loaded may already be out of date
}

function _getImage( name )
{
	// Method to retrieve an existing image from the cache;
	// returns null if the image could not be found
	for (i = 0; i < this.items.length; i ++)
	{
		if (this.items[i].file.indexOf(name) != -1)
			return this.items[i].image;
	}

	return null;
}
