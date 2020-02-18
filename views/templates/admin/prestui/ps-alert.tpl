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
&lt;script type=&quot;riot/tag&quot;&gt;X_RN_X_*_&lt;ps-alert&gt;X_RN_XX_RN_X_*_{if $ps_version >= 1.6}X_RN_XX_RN_X_*_&lt;div class=&quot;alert { opts['alertClass'] }&quot;&gt;X_RN_X_*_&lt;button type=&quot;button&quot; class=&quot;close&quot; data-dismiss=&quot;alert&quot;&gt;&times;&lt;/button&gt;X_RN_X_*_&lt;yield/&gt;X_RN_X_*_&lt;/div&gt;X_RN_XX_RN_X_*_{else}X_RN_XX_RN_X_*_&lt;div class=&quot;{ opts['alertClass'] }&quot;&gt;X_RN_X_*_&lt;yield/&gt;X_RN_X_*_&lt;img class=&quot;close&quot; alt=&quot;X&quot; src=&quot;../img/admin/close.png&quot; onclick={ hide }&gt;X_RN_X_*_&lt;/div&gt;X_RN_XX_RN_X_*_&lt;style scoped&gt;X_RN_XX_RN_X_*_div { X_RN_X_*_position: relative;X_RN_X_*_padding-right: 25px !important;X_RN_X_*_}X_RN_XX_RN_X_*_img.close { X_RN_X_*_margin: 0 !important;X_RN_X_*_position: absolute;X_RN_X_*_right: 10px;X_RN_X_*_top: 15px;X_RN_X_*_cursor: pointer;X_RN_X_*_}X_RN_XX_RN_X_*_&lt;/style&gt;X_RN_XX_RN_X_*_hide(e) { X_RN_X_*_$(e.target).parent().hide()X_RN_X_*_}X_RN_XX_RN_X_*_{/if}X_RN_XX_RN_X_*_&lt;/ps-alert&gt;X_RN_X&lt;/script&gt;X_RN_XX_RN_XX_RN_X&lt;script type=&quot;riot/tag&quot;&gt;X_RN_X_*_&lt;ps-alert-success&gt;X_RN_XX_RN_X_*_&lt;ps-alert alert-class=&quot;{if $ps_version == 1.5}conf{else}alert alert-success{/if}&quot;&gt;X_RN_X_*_&lt;yield/&gt;X_RN_X_*_&lt;/ps-alert&gt;X_RN_XX_RN_X_*_&lt;/ps-alert-success&gt;X_RN_X&lt;/script&gt;X_RN_XX_RN_X&lt;script type=&quot;riot/tag&quot;&gt;X_RN_X_*_&lt;ps-alert-error&gt;X_RN_XX_RN_X_*_&lt;ps-alert alert-class=&quot;{if $ps_version == 1.5}error{else}alert alert-danger{/if}&quot;&gt;X_RN_X_*_&lt;yield/&gt;X_RN_X_*_&lt;/ps-alert&gt;X_RN_XX_RN_X_*_&lt;/ps-alert-error&gt;X_RN_X&lt;/script&gt;X_RN_XX_RN_X&lt;script type=&quot;riot/tag&quot;&gt;X_RN_X_*_&lt;ps-alert-warn&gt;X_RN_XX_RN_X_*_&lt;ps-alert alert-class=&quot;{if $ps_version == 1.5}warn{else}alert alert-warning{/if}&quot;&gt;X_RN_X_*_&lt;yield/&gt;X_RN_X_*_&lt;/ps-alert&gt;X_RN_XX_RN_X_*_&lt;/ps-alert-warn&gt;X_RN_X&lt;/script&gt;X_RN_XX_RN_X&lt;script type=&quot;riot/tag&quot;&gt;X_RN_X_*_&lt;ps-alert-hint&gt;X_RN_XX_RN_X_*_&lt;ps-alert alert-class=&quot;{if $ps_version == 1.5}hint{else}alert alert-info{/if}&quot;&gt;X_RN_X_*_&lt;yield/&gt;X_RN_X_*_&lt;/ps-alert&gt;X_RN_XX_RN_X_*_{if $ps_version == 1.5}X_RN_XX_RN_X_*_&lt;style scoped&gt;X_RN_X_*_.hint { X_RN_X_*_display: block;X_RN_X_*_margin: 0 0 10px 0;X_RN_X_*_}X_RN_X_*_&lt;/style&gt;X_RN_XX_RN_X_*_{/if}X_RN_XX_RN_X_*_&lt;/ps-alert-hint&gt;X_RN_X&lt;/script&gt;