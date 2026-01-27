<?php
//error_reporting(E_ALL);
//echo __FILE__;

//echo $jopa;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="color-scheme" content="dark light">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Output Element Showcase</title>
    <style>
        body {
            font-family: system-ui, sans-serif;
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
            line-height: 1.6;
        }

        section {
            margin: 40px 0;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        h2 {
            margin-top: 0;
        }

        output {
            display: inline-block;
            padding: 4px 8px;
            background: #ccc;
            border: 1px solid #999;
            border-radius: 3px;
            min-width: 60px;
            text-align: center;
        }

        input, button {
            padding: 6px 10px;
            font-size: 16px;
        }

        label {
            display: block;
            margin: 10px 0 5px;
        }
    </style>
</head>
<body>
<h1>The &lt;output&gt; Element</h1>
<p>A native HTML element for displaying calculation results and user action outcomes. Updates are automatically announced to screen readers.</p>

<section>
    <h2>Example 1: Simple Addition</h2>
    <p>The classic calculator example:</p>
    <form oninput="result.value = parseInt(a.value) + parseInt(b.value)">
        <input type="number" id="a" name="a" value="10"> +
        <input type="number" id="b" name="b" value="20"> =
        <output name="result" for="a b">30</output>
    </form>
</section>
</body>
</html>
