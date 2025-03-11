
/* Modified from https://philipwalton.github.io/solved-by-flexbox/demos/media-object/ */

/* Remove left indent from list */
.media-list {
	display:contents;
}

.media-item {
  display: flex;
  align-items: flex-start;
  margin-bottom: 0.5em;
  
  /*
  border-bottom:1px solid rgb(222,222,222);
  padding-bottom:0.5em;
  color:rgb(64,64,64);
  */
}

.media-figure {
  margin-right: 1em;
  display: block;
  width: 100px;
  height: auto;
  border: var(--image-border);
}

.media-body {
  flex: 1;
}

.media-body,
.media-body :last-child {
  margin-bottom: 0;
}

.media-title {
  margin: 0 0 .5em;
  
  color:black;
}

.media--center {
  align-items: center;
}

.media--reverse > .media-figure {
  order: 1;
  margin: 0 0 0 1em;
}
