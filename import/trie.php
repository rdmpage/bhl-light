<?php

// See https://arxiv.org/abs/1711.00046
// Replace or Retrieve Keywords In Documents at Scale
// See also https://github.com/vi3k6i5/flashtext
// PHP Trie largely based on https://www.programmerall.com/article/4530755185/

// Trie for a data object

error_reporting(E_ALL);

mb_internal_encoding("UTF-8");

//----------------------------------------------------------------------------------------
class TrieNode
{
	var $data;
	var $children = array();
	var $isEndChar = false;
	var $thing = null;
	
	var $id = 0;
	
	function __construct ($data)
	{
		$this->data = $data;
	}

}


//----------------------------------------------------------------------------------------
class Trie {

	var $root;
	var $has_wordbreaks = true;
	var $ignore_case = true;

	var $node_count = 0; // debugging

	//------------------------------------------------------------------------------------
	function __construct ($ignore_case = true)
	{
		$this->root = new TrieNode('/');
		$this->$ignore_case = $ignore_case; 
		
		$this->root->id = $this->node_count++;	 // debugging	
	}

	//------------------------------------------------------------------------------------
	// Add a "thing" to the trie. We add the name of the thing (and any alternate names)
	// to the trie, each name ends with a node that has a copy of $thing.
	function add ($thing)
	{
		$strings = array();
		
		$strings[] = $thing->name;
		
		if (isset($thing->alternate_names) && ($thing->alternate_names != ''))
		{
			$strings = array_merge($strings, explode("|", $thing->alternate_names));
		}
		
		$strings = array_unique($strings);
		
		foreach ($strings as $text)
		{
			$p = $this->root;
			
			if ($this->ignore_case)
			{
				$text = mb_strtolower($text);
			}
				
			$len = mb_strlen($text);
			for ($i = 0; $i < $len; $i++)
			{
				// but see https://stackoverflow.com/questions/3666306/how-to-iterate-utf-8-string-in-php/14366023#14366023
		
				$char = mb_substr($text, $i, 1, 'UTF-8');
				
				
				$index = $data = $char;
									
				if (empty($p->children[$index]))
				{
					$newNode = new TrieNode($data);
				
					$newNode->id = $this->node_count++; // debugging
				
					$p->children[$index] = $newNode;				
				}
				$p = $p->children[$index];
			}
	
			$p->isEndChar = true;
		
			$p->thing = $thing;
		}
	}
	
	/*
	//------------------------------------------------------------------------------------
	function find ($text)
	{
		$p = $this->root;
		
		$len = mb_strlen($text);
		for ($i = 0; $i < $len; $i++)
		{
			// but see https://stackoverflow.com/questions/3666306/how-to-iterate-utf-8-string-in-php/14366023#14366023
		
			$char = mb_substr($text, $i, 1, 'UTF-8');
			
			$index = $data = $char;
									
			if (empty($p->children[$index]))
			{
				return false;
			}
			$p = $p->children[$index];
		}
	
		if (!$p->isEndChar)
		{
			return false;
		}
		
		return true;
	
	}
	*/
	
	//------------------------------------------------------------------------------------
	// dump a DOT graph to help with debugging
	function toDot()
	{
		$stack = array();
		
		$stack[] = $this->root;
		
		$endCounter = 0;
		
		$nodes = array();
		$edges = array();
		
		while (!empty($stack))
		{
			$p = array_pop($stack);
						
			$nodes[] =  'node [label="' . $p->data . '"] N' . $p->id . ";";
			
			foreach ($p->children as $q)
			{
				$edges[] =  "N" . $p->id . " -> " . "N" . $q->id . ";";
				$stack[] = $q;
			}

			if ($p->isEndChar)
			{
				//echo "*\n";
				$nodes[] = 'node [label="*"] E' . $endCounter . ";";
				$edges[] = "N" . $p->id . " -> " . "E" . $endCounter. ";";
				$endCounter++;
				$nodes[] = 'node [label="' . $p->thing->name . '"] E' . $endCounter . ";";
				$edges[] = "N" . $p->id . " -> " . "E" . $endCounter. ";";
				$endCounter++;				
			}			
		}	
		
		$dot = '';
		
		$dot .= "digraph G {\n";
		$dot .= join("\n", $nodes);
		$dot .= join("\n", $edges);
		$dot .= "\n}\n";
	
		return $dot;
	}
	
	//------------------------------------------------------------------------------------
	function is_word_character($char)
	{
		// If language has word breaks then test for word character, 
		// otherwise return false
		if ($this->has_wordbreaks)
		{
			return preg_match('/[\p{L}\p{Lu}0-9\-\']/u', $char);
		}
		else
		{
			return false;
		}
	}
	
	//------------------------------------------------------------------------------------
	// Walk along a block of text and find occurrences of words in our trie. Where 
	// possible extend a match to the longest match in the trie
	function flash($sentence, $return_thing = false, $debug = false)
	{
		$things = array();
		
		// If string has Han characters then assume it has no word breaks
		$this->has_wordbreaks = !preg_match('/\p{Han}+/u', $sentence);
				
		$current = $this->root;
		
		$index 				= 0;
		$sequence_end_pos 	= 0;
		$sentence_len 		= mb_strlen($sentence, 'UTF-8');
				
		$sequence_found 			= '';
		$longest_sequence_found 	= '';
		$is_longer_seq_found 		= false;
		
		$thing_found				= null;
		
		if ($this->ignore_case)
		{
			$sentence = mb_strtolower($sentence);
		}
		
		while ($index < $sentence_len)
		{			
			if ($debug)
			{
				echo "|sequence_found=$sequence_found|\n";
				echo "ls=$longest_sequence_found\n";
			}
		
			$char = mb_substr($sentence, $index, 1, 'UTF-8');
			
			// # when we reach a character that might denote word end
			if (!$this->is_word_character($char))
			{		
				if ($debug)
				{
					echo "|$char| is not a word character\n";
				}
			
				// # if eot is present in current_dict
				if ($current->thing || isset($current->children[$char]))
				{
					// # update longest sequence found
					$longest_sequence_found = '';
					$is_longer_seq_found = false;
					
					$thing_found = null;
										
					if ($current->thing)
					{
						$longest_sequence_found = $sequence_found;
						$sequence_end_pos = $index;	
						
						$thing_found = $current->thing;
					}
										
					// # re look for longest_sequence from this position
					if (isset($current->children[$char]))
					{
						$sequence_continued = $sequence_found . $char;
											
						$current_dict_continued = $current->children[$char];
						$indexy = $index + 1;
						
						while ($indexy < $sentence_len)
						{
							$inner_char = mb_substr($sentence, $indexy, 1, 'UTF-8');
						
							if (!$this->is_word_character($inner_char) 
								&& $current_dict_continued->thing )
							{
								 $longest_sequence_found = $sequence_continued;
								 $sequence_end_pos = $indexy;	
								 $is_longer_seq_found = true;					
							}
							if (isset($current_dict_continued->children[$inner_char]))
							{
								$sequence_continued .= $inner_char;
								
								// echo "|sequence_continued=$sequence_continued|\n";
								
								$current_dict_continued = $current_dict_continued->children[$inner_char];
							}
							else
							{
								break;
							}							
							$indexy++;
						}
						
						// # end of sentence reached.
						if ($current_dict_continued->thing)
						{
							// # update longest sequence found
							$longest_sequence_found = $sequence_continued;
							$sequence_end_pos = $indexy;	
							$is_longer_seq_found = true;		
							
							$thing_found = $current_dict_continued->thing;				
						}
						
						if ($is_longer_seq_found )
						{
							$index = $sequence_end_pos;
						}
					}
					
					$current = $this->root;
					
					if ($longest_sequence_found != '' && $thing_found)					
					{					
						$hit = new stdclass;
						$hit->text = $longest_sequence_found;
						$hit->start = $index - mb_strlen($hit->text);
						$hit->end = $index;	
						
						if ($return_thing)
						{
							$hit->thing = $thing_found;
						}

						$things[] = $hit;	
						
						$sequence_found = "";	
						$longest_sequence_found = "";	
						
						/*
						echo "\n---\n";
						echo "emit with longest_sequence_found\n";
						print_r($hit);
						*/
							
					}
				}
				else
				{					
					$sequence_found = "";
					$longest_sequence_found = "";
				
					// # we reset current_dict
					$current = $this->root;					
				}
			}
			else
			{
				// not a word bounary
				if (isset($current->children[$char]))
				{
					$sequence_found  .= $char;			
				
					// # char is present in current dictionary position
					$current = $current->children[$char];
				}
				else
				{
					// # we reset current_dict
					$current = $this->root;
					
					// # skip to end of word
					$indexy = $index + 1;
					while ($index < $sentence_len)
					{
						$char = mb_substr($sentence, $indexy, 1, 'UTF-8');
						
						if (!$this->is_word_character($char))
						{
							break;
						}
						$indexy++;
					}
					$index = $indexy;	
					
					$sequence_found  = '';
					$longest_sequence_found = "";
				}				
			}
			
			// # if we are end of sentence and have a sequence discovered
			if ($index + 1 >= $sentence_len)
			{
				if ($current->thing)
				{
					//$sequence_found = $current->thing->name;
					
					$hit = new stdclass;
					$hit->text = $sequence_found;
					$hit->start = $index - mb_strlen($hit->text);
					$hit->end = $index;
					
					if ($return_thing)
					{
						$hit->thing = $current->thing;
					}
										
					$things[] = $hit;					
				}
			}
		
			$index++;
		
		}
		
		return $things;	
	}


}



?>
