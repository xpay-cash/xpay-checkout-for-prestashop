{*
*    The MIT License (MIT)
*
*    Copyright (c) 2015 Emmanuel MARICHAL
*
*    Permission is hereby granted, free of charge, to any person obtaining a copy
*    of this software and associated documentation files (the "Software"), to deal
*    in the Software without restriction, including without limitation the rights
*    to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
*    copies of the Software, and to permit persons to whom the Software is
*    furnished to do so, subject to the following conditions:
*
*    The above copyright notice and this permission notice shall be included in
*    all copies or substantial portions of the Software.
*
*    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
*    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
*    FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
*    AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
*    LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
*    OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
*    THE SOFTWARE.
*}
X_RN_XX_RN_X{if !isset($tags) || !is_array($tags)}X_RN_X_*_{assign var='tags' value=array('tabs', 'panel', 'form', 'alert', 'table')}X_RN_X{/if}X_RN_XX_RN_X&lt;script type=&quot;text/javascript&quot;&gt;X_RN_X_*_var color_picker = false;X_RN_X&lt;/script&gt;X_RN_XX_RN_X{assign var=&quot;ps_version&quot; value=$smarty.const._PS_VERSION_|string_format:&quot;%.1f&quot;}X_RN_XX_RN_X{foreach from=$tags item=tag}X_RN_X_*_{include file=&quot;./ps-$tag.tpl&quot;}X_RN_X{/foreach}X_RN_XX_RN_X&lt;script type=&quot;riot/tag&quot;&gt;X_RN_X_*_&lt;raw&gt;X_RN_X_*_&lt;span&gt;&lt;/span&gt;X_RN_XX_RN_X_*_this.root.innerHTML = opts.contentX_RN_X_*_&lt;/raw&gt;X_RN_X&lt;/script&gt;X_RN_XX_RN_X&lt;script type=&quot;text/javascript&quot;&gt;X_RN_X_*_riot.mount('*');X_RN_X&lt;/script&gt;