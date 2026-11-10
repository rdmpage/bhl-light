# **BHL Optical Character Recognition (OCR)**

## 

## *Data Dictionary & Description*

Last Updated: 1 June 2026

**Important Note: As of 1 June 2026, the format/organization of the files has changed significantly. Please review the next section carefully.**

This document describes the dataset containing the full export of the 64+ million pages of OCR content in the Biodiversity Heritage Library. The archive is hosted on [Figshare](https://smithsonian.figshare.com/articles/dataset/BHL_Optical_Character_Recognition_OCR_-_Full_Text_Export_new_/21422193).

# **Format / Organization of the Archive**

The file is organized using the BHL ItemID and BHL PartID. Items are usually book-like objects. Parts are usually article-type objects.

Where ItemIDs and PartIDs appear in the archive, they are zero-padded to 6 digits. Each Item or Part has a folder containing the OCR text for the pages of the Item or Part, one file per page. Items or Parts The filenames for the OCR text contain the a prefix for type, the ItemID or PartID,  the PageID, and Sequence Number of the page. 

The items and parts are 

Example: Using ItemIDs 10285 and 124544 and PartID 447237 as examples, we find the following OCR entries in the archive:

`[...]`  
`bhl-ocr-20230215/`  
`bhl-ocr-20230215/item-01/`  
`bhl-ocr-20230215/item-01/item-010285/`  
`bhl-ocr-20230215/item-01/item-010285/item-010285-02896589-0001.txt`  
`bhl-ocr-20230215/item-01/item-010285/item-010285-02896590-0002.txt`  
`bhl-ocr-20230215/item-01/item-010285/item-010285-02896591-0003.txt`  
`[...]`  
`bhl-ocr-20230215/item-12/`  
`bhl-ocr-20230215/item-12/item-124544/`  
`bhl-ocr-20230215/item-12/item-124544/124544-40298468-0001.txt`  
`bhl-ocr-20230215/item-12/item-124544/124544-40298469-0002.txt`  
`bhl-ocr-20230215/item-12/item-124544/124544-40298470-0003.txt`  
`[...]`  
`bhl-ocr-20230215/part-44/`  
`bhl-ocr-20230215/part-44/part-447237/`  
`bhl-ocr-20230215/part-44/part-447237/part-447237-65611801-0001.txt`  
`bhl-ocr-20230215/part-44/part-447237/part-447237-65611802-0002.txt`  
`bhl-ocr-20230215/part-44/part-447237/part-447237-65611803-0003.txt`  
`[...]`

For example, the 5th page image of Item 124544 has PageID 40298472\. ItemIDs and PageIDs can be mapped back to BHL with simple URLs:

[https\://www\.biodiversitylibrary.org/item/124544](https://www.biodiversitylibrary.org/item/124544) (ItemID)  
[https\://www\.biodiversitylibrary.org/part/447237](https://www.biodiversitylibrary.org/part/447237) (PartID)  
[https\://www\.biodiversitylibrary.org/page/40298472](https://www.biodiversitylibrary.org/page/40298472) (PageID)

# **Frequency of Updates**

The file is created monthly on the 15th of the month and is usually uploaded two days later. The filename of the archive contains the date on which the archive was created in YYYYMMDD format. The same date is included in the root folder in the archive.

# **Uncompressing the Archive**

The file is a *.tar.bz2* file created on Linux. The archive is compressed with bzip2 which is supported by many zip/archive software packages, most notably [7-Zip](https://www.7-zip.org/) and [WinRar](https://www.win-rar.com) on Windows. 

Due to size constraints, only the most recent archive is retained on Figshare. Each compressed archive is approximately 40 GB in size. 

# **Sample Usage: Finding the OCR for one Item**

It’s possible to extract from the archive the items for only one item in BHL. However, without advanced command line tools, programs like 7-Zip and WinRar require that you first decompress the archive (removing the *.bz2* part of the file extension) before browsing the archive to extract one folder. 

This is an unfortunate side effect and limitation in Windows. Once decompressed, 7-Zip or WinRar can then be used to browse the *.tar* file to extract only the ItemID folder(s) needed.

If you are on Linux, a command such as the following is used to extract one folder. Please note, it may take a long time for this command to complete due to the size of the archive.

`tar fxj bhl-ocr-20230215.tar.bz2 bhl-ocr-20230215/part-44/part-447237` 

# **Allowed Tags and Mark-up in OCR text**

Below is a list of tags that are allowed in BHL OCR text for manual transcription projects. If you wish to do computational analysis on the BHL OCR text file in aggregate, please note that there is markup that you may wish to isolate, remove and/or use in some way in your analysis. Decisions made by BHL committee members on syntax were made in the Summer of 2023\. General syntax is:

1. Text additions syntax: \[ text addition \] 

2. Allowable mark-up syntax: \[\[allowed mark-up\]\] text \[\[/allowed mark-up\]\]

| Name | Definition | BHL Tag | BHL Tag Examples |
| :---- | :---- | :---- | :---- |
| **additions** | You may use text **additions** markup to add text that improves full-text search. Text additions are mostly used for: **1\. authorized species names:** type the currently accepted name that is used to refer to the species; this is as opposed to a synonym or common name which often occurs in historical literature. **2\. ditto marks e.g. “ “:** type the text that the repeated entry above it is intended to stand for. **3\. outdated place names:** type the modern accepted place name  **4\. expanded text:** type the expanded text if abbreviated; normal contractions (e.g. "didn't" for did not) should **not** be expanded.  **5\. gender symbols:** type male, female, or intersex for the pictogram or glyph used to represent sex and gender **6\. dates:** type implicit dates using [ISO (ISO 8601\)](https://www.iso.org/iso-8601-date-and-time-format.html) YYYY-MM-DD format 7\. **other:** you may use the additions mark-up for relevant data not codified here. | \[text addition\] | **1\. authorized species names:** Erica herbacea \[Erica carnea\] **2\. ditto marks:** \[repeated text\] **3\. outdated place names:** Soviet Union \[Russia\] **4\. expanded text:** "Sr" should be rendered \[Sir\]. Likewise, the anti-honorific "humle Srvt" can be expanded to \[humble Servant\]. note: normal contractions (e.g. "didn't" for did not) should NOT be expanded. **5\. gender symbols**  Use this markup to add any other text to provide meaning as needed. ♂should be rendered \[male\] ♀should be rendered \[female\] ⚥ should be rendered \[intersex\] **6\. dates:**  \[1951-06-09\</add\] |
| **footnotes and endnotes** | contains the body of in-text citations and/or ancillary descriptive pieces of information found in text. This tag should be placed where the footnote marker appears in the text. Use the HTML footnote tag for endnotes as well. | \[\[footnote\]\] \[\[/footnote\]\]; | \[\[footnote\]\]Vladykov, Vadim D. 1943\. "Relation Between Fish and Fish-Eating Birds." The Canadian field-naturalist 57(7-8), 124–132. \[\[/footnote\]\] |
| **illegible and unclear text** | for a word or phrase in text that is difficult to read | \[\[unclear\]\] \[\[/unclear\]\] | \[\[unclear\]\]some hard to read text\[\[/unclear\]\] |
| **images and media** | to indicate a figure or illustration in the text eg.: illustrations diagrams photos figures stamps watermarks drawings plates etc. add the image alt text/description using this tag. | \[\[illustration\]\] \[\[/illustration\]\]; | \[\[illustration\]\]alt text\[/illustration\]\]; |
| **marginalia** | for noting comments in the margin of a book | \[\[margin\]\] \[\[/margin\]\]; | \[\[margin\]\]some side comments\[\[/margin\]\]; [Example from Harvard Ernst Mayr Library](https://www.biodiversitylibrary.org/item/159921#page/8/mode/1up) |
| **missing text** | for missing text due to torn or missing pages, | \[\[loss\]\] \[\[/loss\]\] |  |
| **strikethroughs** | for text deletions or mistakes in a sentence or paragraph where a straight horizontal line is drawn through. | \[\[strike\]\] \[\[/strike\]\]; | \[strike\]\]strukthrough text\[\[/strike\]\]; |
| **tables** | for tables with headers, rows, and columns. | \| Table Header 1 \| Table Header 2 \| Table Header 3 \| \|-------------------------\|----------------------\|-------------------\| \| data cell 1 \| data cell 2 \| data cell 3 \| \| data cell 4 \| data cell 5 \| data cell 6 \| | \| Company \| Contact \| Country \| \|-------------------------\|----------------------\|-------------------\| \| Alfreds Futterkiste \| Maria Anders \| Germany \| \| Centro comercial Moctezuma \| Francisco Chang \| Mexico \| [Example from NAL.](https://www.biodiversitylibrary.org/page/61938140#page/9/mode/1up) |
| **underlined text** | for underlined text | \[\[underline\]\] \[\[/underline\]\]; | \[\[u\]\]underlined text\[\[/u\]\] |

# 