
//Overide WindowUtilities getPageSize to remove dock height (for maximized windows)
WindowUtilities._oldGetPageSize = WindowUtilities.getPageSize;
WindowUtilities.getPageSize = function() {
	var size = WindowUtilities._oldGetPageSize();
	//Если dock не размещен, то никаких изменений
	if (null == $('dock')) {
		return size;
	}
	var dockHeight = $('dock').getHeight();
  
//	size.pageHeight -= dockHeight;
//	size.windowHeight -= dockHeight;
	return size;
};    


// Overide Windows minimize to move window inside dock  
Object.extend(Windows, {
	// Overide minimize function
	minimize: function(id, event) {
		var win = this.getWindow(id);
		if (win && win.visible) {
			//Если dock не размещен, то будем сворачивать как обычно
			if (null == $('dock')) {
		        win.minimize();
		        return;
			}
			//Hide current window
			var caption = win.getTitle().stripTags();
			//win.hide();
			$(id).setStyle({'display': 'none'});

			// Create a dock element
			var element = document.createElement("span");
			element.className = "dock_icon"; 
//      	element.style.display = "none";
			element.win = win;
			$('dock').appendChild(element);
			Event.observe(element, "mouseup", Windows.restore);
			$(element).update(caption);
//      	new Effect.Appear(element);
		}
		Event.stop(event);
  	},                 
  
  	// Restore function
  	restore: function(event) {
		//Если dock не размещен, то будем разворачивать как обычно
		if (null == $('dock')) {
			return;
		}
  		var element = Event.element(event);
  		// Show window
  		element.win.show();
  		//Windows.focus(element.win.getId());                    
  		element.win.toFront();
  		// Fade and destroy icon
//    	new Effect.Fade(element, {afterFinish: function() {element.remove()}})
  		element.remove();
  	}
});

// blur focused window if click on document
Event.observe(document, "click", function(event) {   
  var e = Event.element(event);
  var win = e.up(".dialog");
  var dock = e == $('dock') || e.up("#dock"); 
  if (!win && !dock && Windows.focusedWindow) {
    Windows.blur(Windows.focusedWindow.getId());                    
  }
});           


// miv 28.07.2010: исправления ошибок объекта window
// getFocusedWindow: если нет выделенного окна, то будет выделено самое верхнее окно (нужно для on_after_save)
Object.extend(Windows, {
	getFocusedWindow: function() {
		if (this.focusedWindow == null)
			this.focusLastWindow();
		return this.focusedWindow;
	},
	  
	focusLastWindow: function() {
		var maxzindex = 0;
		var topwindow = null;
		Windows.windows.each( function(win) {
			if (win.element.style.zIndex > maxzindex) {
				topwindow = win;
				maxzindex = win.element.style.zIndex;
			}
		});
		if (topwindow != null)
			this.focus(topwindow.getId());
	}
});

// Для диалогов исправлено получения окна через Windows.getFocusedWindow()
// если в модальном окне нажать на забеленное пространство (пальто), то кнопки ОК и отмена диалога не работали
Object.extend(Dialog, {
okCallback: function() {
    var win = Windows.getFocusedWindow();
    if (!win.okCallback || win.okCallback(win)) {
      // Remove onclick on button
      $$("#" + win.getId()+" input").each(function(element) {element.onclick=null;})
      win.close();
    }
  },

cancelCallback: function() {
    var win = Windows.getFocusedWindow();
    // Remove onclick on button
    $$("#" + win.getId()+" input").each(function(element) {element.onclick=null})
    win.close();
    if (win.cancelCallback)
      win.cancelCallback(win);
  }
});