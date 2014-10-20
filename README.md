teamcity-behat-formatter
========================

Behat tests formatter for TeamCity

behat.xml:

<pre>
default:
  formatter:
    name: teamcity
  extensions:
    Behat\TeamCity\TeamCityFormatterExtension:
      name: teamcity
</pre>
