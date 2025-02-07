import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

class EmailForm extends StatefulWidget {
  @override
  _EmailFormState createState() => _EmailFormState();
}

class _EmailFormState extends State<EmailForm> {
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _subjectController = TextEditingController();
  final TextEditingController _bodyController = TextEditingController();
  bool _isLoading = false;
  String? _message;

  Future<void> _sendEmail() async {
    final String email = _emailController.text.trim();
    final String subject = _subjectController.text.trim();
    final String body = _bodyController.text.trim();

    if (email.isEmpty || !email.contains("@")) {
      setState(() {
        _message = "Please enter a valid email address.";
      });
      return;
    }

    if (subject.isEmpty || body.isEmpty) {
      setState(() {
        _message = "Subject and Body cannot be empty.";
      });
      return;
    }

    setState(() {
      _isLoading = true;
      _message = null;
    });

    try {
      final response = await http.post(
        Uri.parse('http://34.44.181.13/PRS-API-UTIL/web_API/email.php'),
        headers: {
          'Content-Type': 'application/json',
        },
        body: jsonEncode({
          'email': email,
          'subject': subject,
          'body': body,
        }),
      );

      setState(() {
        _message = response.statusCode == 200
            ? "Email sent successfully!"
            : "Failed to send email. Try again.";
      });
    } catch (error) {
      setState(() {
        _message = "Error: Unable to connect to the server.";
      });
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text("Send Email")),
      body: Padding(
        padding: EdgeInsets.all(20),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            TextField(
              controller: _emailController,
              keyboardType: TextInputType.emailAddress,
              decoration: InputDecoration(
                labelText: "Enter recipient's email",
                border: OutlineInputBorder(),
              ),
            ),
            SizedBox(height: 10),
            TextField(
              controller: _subjectController,
              decoration: InputDecoration(
                labelText: "Enter Subject",
                border: OutlineInputBorder(),
              ),
            ),
            SizedBox(height: 10),
            TextField(
              controller: _bodyController,
              maxLines: 5,
              decoration: InputDecoration(
                labelText: "Enter Email Body",
                border: OutlineInputBorder(),
              ),
            ),
            SizedBox(height: 20),
            _isLoading
                ? CircularProgressIndicator()
                : ElevatedButton(
                    onPressed: _sendEmail,
                    child: Text("Send Email"),
                  ),
            if (_message != null)
              Padding(
                padding: const EdgeInsets.all(8.0),
                child: Text(
                  _message!,
                  style: TextStyle(
                    color: _message == "Email sent successfully!"
                        ? Colors.green
                        : Colors.red,
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}
